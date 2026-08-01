<?php

namespace App\Services;

use App\Models\Lembaga;
use App\Models\RekeningTabungan;
use App\Models\SaldoSantri;
use App\Models\TagihanPembayaran;
use App\Models\TopupWali;
use App\Models\Transaksi;
use App\Models\TransaksiTabungan;
use App\Models\UnitUsaha;
use App\Models\UnitUsahaPenarikan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A general ledger for the pondok's OWN cash position - separate from
 * saldo_santris (which is money the pondok holds *on behalf of* santri, a
 * custodial liability, not the pondok's own cash). This is entirely derived
 * from existing immutable records (never a second source of truth):
 *
 *  - Kas masuk (cash in): Transaksi topup_tunai (cash handed to a kasir),
 *    TopupWali paid (the full nominal_diminta - whether Midtrans money ends
 *    up covering a tagihan or landing in saldo, it still hit the pondok's
 *    Midtrans balance the moment the topup settled), and TagihanPembayaran
 *    sumber=tunai_langsung (a bill paid with cash directly at the counter,
 *    which never touches the wallet/Transaksi ledger at all).
 *  - Kas keluar (cash out): Transaksi penarikan_tunai (cash handed back to
 *    a santri/wali), a fulfilled UnitUsahaPenarikan (kantin owner's
 *    saldo_unit cashed out via a real bank transfer or physical cash handoff
 *    by the admin - a genuine kas-pondok outflow classified according to its
 *    disbursement method), and a paid TopupWali's Midtrans fee when the
 *    admin's MidtransFeeService policy leaves it pondok-borne (see
 *    entriBiayaMidtransAdmin()) - Midtrans still deducts its real fee from
 *    settlement even though the santri was credited the topup's full
 *    amount, so that gap is a genuine, previously-invisible expense.
 *
 * Deliberately EXCLUDED: Transaksi pembayaran_tagihan (paid from saldo),
 * penyesuaian, and Transaksi pembayaran_kantin (a santri paying a kantin via
 * the app). None of these move physical/bank cash in or out of the pondok's
 * own position - they only reclassify who already-received money is held
 * for (santri's spendable saldo -> kantin's payable saldo_unit, in the
 * pembayaran_kantin case), the same reasoning as pembayaran_tagihan below.
 * Counting them here would double-count cash that topup_tunai/TopupWali
 * already recorded. transfer_wali_otomatis rows in tagihan_pembayarans are
 * excluded for the same reason: that cash is already counted once via the
 * TopupWali row's full nominal_diminta.
 */
class LegerKasPondokService
{
    public const SUMBER_TUNAI = 'tunai';

    public const SUMBER_MIDTRANS = 'midtrans';

    public const SUMBER_TRANSFER_BANK = 'transfer_bank';

    /**
     * @return array{
     *     tanggal_dari: Carbon, tanggal_sampai: Carbon, lembaga: ?Lembaga,
     *     saldo_awal: int, saldo_akhir: int, total_masuk: int, total_keluar: int,
     *     total_masuk_tunai: int, total_masuk_midtrans: int, total_keluar_tunai: int,
     *     total_keluar_transfer_bank: int, total_keluar_kantin_tunai: int,
     *     entri: array<int, array{tanggal: Carbon, jenis: string, keterangan: string, pihak: string, sumber_dana: string, masuk: int, keluar: int, saldo_berjalan: int}>,
     *     kas_saat_ini: int, saldo_santri_saat_ini: int, saldo_kantin_belum_cair: int, uang_milik_pondok: int,
     * }
     */
    public function generate(Carbon $tanggalDari, Carbon $tanggalSampai, ?int $lembagaId = null, ?string $sumberDana = null): array
    {
        $dari = $tanggalDari->copy()->startOfDay();
        $sampai = $tanggalSampai->copy()->endOfDay();

        $saldoAwal = $this->totalSebelum($dari, $lembagaId, $sumberDana);

        $entri = $this->entriDalamRentang($dari, $sampai, $lembagaId, $sumberDana)
            ->sortBy('tanggal')
            ->values();

        $berjalan = $saldoAwal;
        $entri = $entri->map(function (array $row) use (&$berjalan) {
            $berjalan += $row['masuk'] - $row['keluar'];
            $row['saldo_berjalan'] = $berjalan;

            return $row;
        });

        // Totals within the range only (not affected by sumber_dana filter,
        // so the on-screen breakdown always shows the full tunai/midtrans/
        // transfer_bank split regardless of which one is currently filtered
        // for the list).
        $entriTakDifilter = $this->entriDalamRentang($dari, $sampai, $lembagaId, null);

        $milikPondok = $this->uangMilikPondokSaatIni($lembagaId);

        return [
            'tanggal_dari' => $dari,
            'tanggal_sampai' => $sampai,
            'lembaga' => $lembagaId ? Lembaga::find($lembagaId) : null,
            'saldo_awal' => $saldoAwal,
            'saldo_akhir' => $berjalan,
            'total_masuk' => (int) $entri->sum('masuk'),
            'total_keluar' => (int) $entri->sum('keluar'),
            'total_masuk_tunai' => (int) $entriTakDifilter->where('sumber_dana', self::SUMBER_TUNAI)->sum('masuk'),
            'total_masuk_midtrans' => (int) $entriTakDifilter->where('sumber_dana', self::SUMBER_MIDTRANS)->sum('masuk'),
            'total_keluar_tunai' => (int) $entriTakDifilter->where('sumber_dana', self::SUMBER_TUNAI)->sum('keluar'),
            'total_keluar_transfer_bank' => (int) $entriTakDifilter->where('sumber_dana', self::SUMBER_TRANSFER_BANK)->sum('keluar'),
            'total_keluar_kantin_tunai' => (int) $entriTakDifilter
                ->where('jenis', 'Pencairan Tunai Kantin')
                ->sum('keluar'),
            'entri' => $entri->all(),
            ...$milikPondok,
        ];
    }

    /**
     * Not all of the pondok's kas is the pondok's own money - most of it is
     * held in custody: santri's own spendable saldo (a liability, refundable/
     * spendable by them any time) and a kantin owner's saldo_unit not yet
     * cashed out (also owed to someone else, just not paid out yet). What's
     * genuinely the pondok's - typically money already collected via
     * tagihan payments - is whatever's left after subtracting both.
     *
     * Deliberately always "right now" (like saldo_santri_saat_ini on
     * Laporan Keuangan), not scoped to the report's selected date range:
     * saldo_santris/UnitUsaha.saldo_unit only ever hold each entity's
     * CURRENT balance (no historical snapshots), so there's no correct way
     * to know what they were as of some past tanggal_sampai - only "now" is
     * answerable without reconstructing the entire ledger's history.
     *
     * saldo_kantin_belum_cair has no lembaga of its own, so it's excluded
     * (treated as 0) whenever a specific lembaga is filtered - same
     * exclusion as entriKantinPencairan() above, for the same reason.
     */
    private function uangMilikPondokSaatIni(?int $lembagaId): array
    {
        $kasSaatIni = (int) $this->entriDalamRentang(null, now(), $lembagaId, null)
            ->sum(fn (array $row) => $row['masuk'] - $row['keluar']);

        $saldoSantri = (int) SaldoSantri::query()
            ->when($lembagaId, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where('lembaga_id', $lembagaId)))
            ->sum('saldo');

        $saldoKantin = $lembagaId ? 0 : (int) UnitUsaha::sum('saldo_unit');
        $saldoTabungan = (int) RekeningTabungan::query()
            ->when($lembagaId, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where('lembaga_id', $lembagaId)))
            ->sum('saldo');

        return [
            'kas_saat_ini' => $kasSaatIni,
            'saldo_santri_saat_ini' => $saldoSantri,
            'saldo_tabungan_saat_ini' => $saldoTabungan,
            'saldo_kantin_belum_cair' => $saldoKantin,
            'uang_milik_pondok' => $kasSaatIni - $saldoSantri - $saldoTabungan - $saldoKantin,
        ];
    }

    private function totalSebelum(Carbon $dari, ?int $lembagaId, ?string $sumberDana): int
    {
        $entri = $this->entriDalamRentang(null, $dari->copy()->subSecond(), $lembagaId, $sumberDana);

        return (int) $entri->sum('masuk') - (int) $entri->sum('keluar');
    }

    /**
     * @return Collection<int, array{tanggal: Carbon, jenis: string, keterangan: string, pihak: string, sumber_dana: string, masuk: int, keluar: int}>
     */
    private function entriDalamRentang(?Carbon $dari, Carbon $sampai, ?int $lembagaId, ?string $sumberDana): Collection
    {
        $entri = collect();

        if ($sumberDana === null || $sumberDana === self::SUMBER_TUNAI) {
            $entri = $entri
                ->merge($this->entriTopupTunai($dari, $sampai, $lembagaId))
                ->merge($this->entriTagihanTunaiLangsung($dari, $sampai, $lembagaId))
                ->merge($this->entriSetoranTabunganTunai($dari, $sampai, $lembagaId))
                ->merge($this->entriPenarikanTunai($dari, $sampai, $lembagaId));

            if (! $lembagaId) {
                $entri = $entri->merge(
                    $this->entriKantinPencairan($dari, $sampai, UnitUsahaPenarikan::METODE_TUNAI)
                );
            }
        }

        if ($sumberDana === null || $sumberDana === self::SUMBER_MIDTRANS) {
            $entri = $entri
                ->merge($this->entriTopupWali($dari, $sampai, $lembagaId))
                ->merge($this->entriBiayaMidtransAdmin($dari, $sampai, $lembagaId));
        }

        // Kantin pencairan has no santri/lembaga of its own - excluded
        // entirely once a specific lembaga filter is active, same as it
        // would be if it genuinely belonged to none of them.
        if (($sumberDana === null || $sumberDana === self::SUMBER_TRANSFER_BANK) && ! $lembagaId) {
            $entri = $entri->merge(
                $this->entriKantinPencairan($dari, $sampai, UnitUsahaPenarikan::METODE_TRANSFER_BANK)
            );
        }

        return $entri;
    }

    private function entriTopupTunai(?Carbon $dari, Carbon $sampai, ?int $lembagaId): Collection
    {
        return Transaksi::query()
            ->where('jenis', Transaksi::JENIS_TOPUP_TUNAI)
            ->where('status', Transaksi::STATUS_BERHASIL)
            ->when($dari, fn ($q) => $q->where('created_at', '>=', $dari))
            ->where('created_at', '<=', $sampai)
            ->when($lembagaId, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where('lembaga_id', $lembagaId)))
            ->with('santri:id,nama')
            ->get()
            ->map(fn (Transaksi $t) => [
                'tanggal' => $t->created_at,
                'jenis' => 'Top Up Tunai',
                'keterangan' => 'Top up saldo santri via kasir',
                'pihak' => $t->santri?->nama ?? '(santri dihapus)',
                'sumber_dana' => self::SUMBER_TUNAI,
                'masuk' => (int) $t->nominal,
                'keluar' => 0,
            ]);
    }

    private function entriPenarikanTunai(?Carbon $dari, Carbon $sampai, ?int $lembagaId): Collection
    {
        return Transaksi::query()
            ->where('jenis', Transaksi::JENIS_PENARIKAN_TUNAI)
            ->where('status', Transaksi::STATUS_BERHASIL)
            ->when($dari, fn ($q) => $q->where('created_at', '>=', $dari))
            ->where('created_at', '<=', $sampai)
            ->when($lembagaId, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where('lembaga_id', $lembagaId)))
            ->with('santri:id,nama')
            ->get()
            ->map(fn (Transaksi $t) => [
                'tanggal' => $t->created_at,
                'jenis' => 'Penarikan Tunai',
                'keterangan' => 'Penarikan tunai oleh santri',
                'pihak' => $t->santri?->nama ?? '(santri dihapus)',
                'sumber_dana' => self::SUMBER_TUNAI,
                'masuk' => 0,
                'keluar' => (int) $t->nominal,
            ]);
    }

    private function entriSetoranTabunganTunai(?Carbon $dari, Carbon $sampai, ?int $lembagaId): Collection
    {
        return TransaksiTabungan::query()
            ->where('jenis', TransaksiTabungan::JENIS_SETORAN_TUNAI)
            ->where('status', Transaksi::STATUS_BERHASIL)
            ->when($dari, fn ($q) => $q->where('created_at', '>=', $dari))
            ->where('created_at', '<=', $sampai)
            ->when($lembagaId, fn ($q) => $q->whereHas('rekening.santri', fn ($q) => $q->where('lembaga_id', $lembagaId)))
            ->with(['rekening.santri:id,nama', 'sesiKas:id,nomor'])
            ->get()
            ->map(fn (TransaksiTabungan $transaksi) => [
                'tanggal' => $transaksi->created_at,
                'jenis' => 'Setoran Tunai Tabungan',
                'keterangan' => 'Setoran tunai melalui sesi kas '.($transaksi->sesiKas?->nomor ?? '-'),
                'pihak' => $transaksi->rekening?->santri?->nama ?? '(santri dihapus)',
                'sumber_dana' => self::SUMBER_TUNAI,
                'masuk' => (int) $transaksi->nominal,
                'keluar' => 0,
            ]);
    }

    private function entriTagihanTunaiLangsung(?Carbon $dari, Carbon $sampai, ?int $lembagaId): Collection
    {
        return TagihanPembayaran::query()
            ->where('sumber', TagihanPembayaran::SUMBER_TUNAI_LANGSUNG)
            ->when($dari, fn ($q) => $q->where('dibayar_at', '>=', $dari))
            ->where('dibayar_at', '<=', $sampai)
            ->when($lembagaId, fn ($q) => $q->whereHas('tagihan.santri', fn ($q) => $q->where('lembaga_id', $lembagaId)))
            ->with(['tagihan.santri:id,nama', 'tagihan.jenisTagihan:id,nama'])
            ->get()
            ->map(fn (TagihanPembayaran $p) => [
                'tanggal' => $p->dibayar_at,
                'jenis' => 'Bayar Tagihan Tunai',
                'keterangan' => 'Bayar tagihan '.($p->tagihan?->jenisTagihan?->nama ?? '(tagihan dihapus)').' langsung tunai',
                'pihak' => $p->tagihan?->santri?->nama ?? '(santri dihapus)',
                'sumber_dana' => self::SUMBER_TUNAI,
                'masuk' => (int) $p->nominal,
                'keluar' => 0,
            ]);
    }

    private function entriTopupWali(?Carbon $dari, Carbon $sampai, ?int $lembagaId): Collection
    {
        return TopupWali::query()
            ->where('status', TopupWali::STATUS_PAID)
            ->when($dari, fn ($q) => $q->where('paid_at', '>=', $dari))
            ->where('paid_at', '<=', $sampai)
            ->when($lembagaId, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where('lembaga_id', $lembagaId)))
            ->with(['santri:id,nama', 'tagihan.jenisTagihan:id,nama'])
            ->get()
            ->map(fn (TopupWali $t) => [
                'tanggal' => $t->paid_at,
                'jenis' => $this->jenisTopupWali($t),
                'keterangan' => 'Top up via Midtrans (VA/QRIS/Snap)',
                'pihak' => $t->santri?->nama ?? '(santri dihapus)',
                'sumber_dana' => self::SUMBER_MIDTRANS,
                'masuk' => (int) $t->nominal_diminta,
                'keluar' => 0,
            ]);
    }

    /**
     * Distinguishes *why* the money came in - it's still the same cash
     * hitting the pondok's Midtrans balance either way (that's why this
     * doesn't split into separate rows), but the Jenis column is the only
     * per-row signal shown on screen/Excel, and "Top Up Transfer Wali" for
     * every row gave no way to tell a tagihan payment apart from a plain
     * saldo top up without opening each one - exactly what a reconciling
     * admin needs to see at a glance. Mirrors the distinction Laporan
     * Keuangan already makes in aggregate (total_ke_tagihan vs
     * total_ke_saldo). A tagihan-scoped top up can still split across both
     * if the tagihan couldn't absorb the full amount (see
     * TopupWaliService::settleTagihanScoped()), hence the three cases.
     */
    private function jenisTopupWali(TopupWali $t): string
    {
        if ($t->tujuan === TopupWali::TUJUAN_TABUNGAN) {
            return 'Setoran Tabungan via Midtrans';
        }

        $bayarTagihan = (int) $t->nominal_potongan_tagihan;
        $keSaldo = (int) $t->nominal_ke_saldo;
        $namaTagihan = $t->tagihan?->jenisTagihan?->nama;

        if ($bayarTagihan > 0 && $keSaldo > 0) {
            return 'Top Up - Bayar Tagihan'.($namaTagihan ? " {$namaTagihan}" : '').' + Saldo';
        }

        if ($bayarTagihan > 0) {
            return 'Top Up - Bayar Tagihan'.($namaTagihan ? " {$namaTagihan}" : '');
        }

        return 'Top Up - Saldo';
    }

    /**
     * When a topup's Midtrans fee is pondok-borne (MidtransFeeService policy
     * at charge time), Midtrans still deducts its real fee from settlement
     * even though the santri was credited the topup's full nominal_diminta -
     * a real cash outflow the pondok's bank balance actually experiences,
     * with no counterparty santri/unit (hence the fixed "Midtrans" pihak,
     * unlike every other entry type in this file). When the fee is instead
     * wali-borne, the surcharge already added to gross_amount offsets
     * Midtrans's deduction, so entriTopupWali()'s existing masuk =
     * nominal_diminta already reflects real settled cash correctly and no
     * extra row is needed here.
     */
    private function entriBiayaMidtransAdmin(?Carbon $dari, Carbon $sampai, ?int $lembagaId): Collection
    {
        return TopupWali::query()
            ->where('status', TopupWali::STATUS_PAID)
            ->where('biaya_ditanggung_wali', false)
            ->where('biaya_midtrans', '>', 0)
            ->when($dari, fn ($q) => $q->where('paid_at', '>=', $dari))
            ->where('paid_at', '<=', $sampai)
            ->when($lembagaId, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where('lembaga_id', $lembagaId)))
            ->get()
            ->map(fn (TopupWali $t) => [
                'tanggal' => $t->paid_at,
                'jenis' => 'Biaya Admin Midtrans',
                'keterangan' => 'Biaya transaksi Midtrans ditanggung pondok',
                'pihak' => 'Midtrans',
                'sumber_dana' => self::SUMBER_MIDTRANS,
                'masuk' => 0,
                'keluar' => (int) $t->biaya_midtrans,
            ]);
    }

    private function entriKantinPencairan(?Carbon $dari, Carbon $sampai, string $metode): Collection
    {
        return UnitUsahaPenarikan::query()
            ->where('status', UnitUsahaPenarikan::STATUS_SELESAI)
            ->where('metode_pencairan', $metode)
            ->when($dari, fn ($q) => $q->where('diproses_at', '>=', $dari))
            ->where('diproses_at', '<=', $sampai)
            ->with('unitUsaha:id,nama')
            ->get()
            ->map(fn (UnitUsahaPenarikan $p) => [
                'tanggal' => $p->diproses_at,
                'jenis' => $p->metode_pencairan === UnitUsahaPenarikan::METODE_TUNAI
                    ? 'Pencairan Tunai Kantin'
                    : 'Pencairan Kantin',
                'keterangan' => $p->metode_pencairan === UnitUsahaPenarikan::METODE_TUNAI
                    ? 'Pencairan saldo kantin secara tunai'
                    : 'Pencairan saldo kantin - ref. transfer: '.($p->referensi_transfer ?? '-'),
                'pihak' => $p->unitUsaha?->nama ?? '(unit usaha dihapus)',
                'sumber_dana' => $p->metode_pencairan === UnitUsahaPenarikan::METODE_TUNAI
                    ? self::SUMBER_TUNAI
                    : self::SUMBER_TRANSFER_BANK,
                'masuk' => 0,
                'keluar' => (int) $p->nominal_diminta,
            ]);
    }
}

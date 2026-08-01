<?php

namespace App\Services;

use App\Models\Lembaga;
use App\Models\PenarikanRequest;
use App\Models\RekeningTabungan;
use App\Models\SaldoSantri;
use App\Models\Tagihan;
use App\Models\TopupWali;
use App\Models\Transaksi;
use App\Models\TransaksiTabungan;
use Illuminate\Support\Carbon;

class LaporanKeuanganService
{
    private const JENIS_TRANSAKSI_LABEL = [
        Transaksi::JENIS_TOPUP_TUNAI => 'Top Up Tunai',
        Transaksi::JENIS_TOPUP_TRANSFER_WALI => 'Top Up Transfer Wali',
        Transaksi::JENIS_PENARIKAN_TUNAI => 'Penarikan Tunai',
        Transaksi::JENIS_PEMBAYARAN_TAGIHAN => 'Pembayaran Tagihan',
        Transaksi::JENIS_PENYESUAIAN => 'Penyesuaian',
        Transaksi::JENIS_PEMBAYARAN_KANTIN => 'Pembayaran Kantin',
        Transaksi::JENIS_TRANSFER_ANTAR_SANTRI => 'Transfer Antar Santri',
        Transaksi::JENIS_TRANSFER_KE_TABUNGAN => 'Saldo ke Tabungan',
        TransaksiTabungan::JENIS_SETORAN_TUNAI => 'Setoran Tunai Tabungan',
        TransaksiTabungan::JENIS_SETORAN_DARI_SALDO => 'Setoran Tabungan dari Saldo',
        TransaksiTabungan::JENIS_SETORAN_MIDTRANS => 'Setoran Tabungan via Midtrans',
    ];

    /**
     * Builds every section of the report from the same filters, so the
     * Livewire page, Excel export, and PDF export can never drift apart.
     *
     * @return array{
     *     tanggal_dari: Carbon, tanggal_sampai: Carbon, lembaga: ?Lembaga,
     *     saldo_santri_saat_ini: int,
     *     transaksi: array{total_kredit: int, total_debit: int, net: int, per_jenis: array<int, array{jenis: string, label: string, jumlah: int, total: int}>},
     *     tagihan: array{total_sebelum_diskon: int, total_diskon: int, total_nominal: int, total_terbayar: int, total_sisa: int, total_santri_bayar: int, per_jenis: array<int, array{nama: string, jumlah: int, sebelum_diskon: int, diskon: int, nominal: int, terbayar: int, sisa: int, santri_bayar: int}>},
     *     topup_wali: array{jumlah: int, total_diminta: int, total_ke_tagihan: int, total_ke_saldo: int},
     *     penarikan: array{jumlah: int, total: int},
     * }
     */
    public function generate(Carbon $tanggalDari, Carbon $tanggalSampai, ?int $lembagaId = null): array
    {
        $dari = $tanggalDari->copy()->startOfDay();
        $sampai = $tanggalSampai->copy()->endOfDay();

        return [
            'tanggal_dari' => $dari,
            'tanggal_sampai' => $sampai,
            'lembaga' => $lembagaId ? Lembaga::find($lembagaId) : null,
            'saldo_santri_saat_ini' => $this->saldoSantriSaatIni($lembagaId),
            'saldo_tabungan_saat_ini' => $this->saldoTabunganSaatIni($lembagaId),
            'transaksi' => $this->ringkasanTransaksi($dari, $sampai, $lembagaId),
            'tagihan' => $this->ringkasanTagihan($dari, $sampai, $lembagaId),
            'topup_wali' => $this->ringkasanTopupWali($dari, $sampai, $lembagaId),
            'penarikan' => $this->ringkasanPenarikan($dari, $sampai, $lembagaId),
        ];
    }

    private function saldoSantriSaatIni(?int $lembagaId): int
    {
        return (int) SaldoSantri::query()
            ->when($lembagaId, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where('lembaga_id', $lembagaId)))
            ->sum('saldo');
    }

    private function saldoTabunganSaatIni(?int $lembagaId): int
    {
        return (int) RekeningTabungan::query()
            ->when($lembagaId, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where('lembaga_id', $lembagaId)))
            ->sum('saldo');
    }

    /**
     * Jenis that represent real money crossing the pondok's own boundary -
     * everything else (transfer_antar_santri, pembayaran_tagihan,
     * pembayaran_kantin, penyesuaian) only reclassifies saldo that's
     * already inside the pondok's custody, the same distinction
     * LegerKasPondokService draws for its own kas masuk/keluar. Kept here
     * (not reused from there) since this list is keyed by Transaksi::jenis,
     * while LegerKasPondokService reasons per data source (TopupWali,
     * TagihanPembayaran, ...) rather than per Transaksi row.
     */
    private const JENIS_KAS_MASUK = [Transaksi::JENIS_TOPUP_TUNAI, Transaksi::JENIS_TOPUP_TRANSFER_WALI];

    private const JENIS_KAS_KELUAR = [Transaksi::JENIS_PENARIKAN_TUNAI];

    private function ringkasanTransaksi(Carbon $dari, Carbon $sampai, ?int $lembagaId): array
    {
        $rows = Transaksi::query()
            ->selectRaw('jenis, arah, count(*) as jumlah, sum(nominal) as total')
            ->where('status', Transaksi::STATUS_BERHASIL)
            ->whereBetween('created_at', [$dari, $sampai])
            ->when($lembagaId, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where('lembaga_id', $lembagaId)))
            ->groupBy('jenis', 'arah')
            ->get();

        $perJenis = [];
        $totalKredit = 0;
        $totalDebit = 0;

        foreach ($rows as $row) {
            $perJenis[$row->jenis] ??= ['jenis' => $row->jenis, 'label' => self::JENIS_TRANSAKSI_LABEL[$row->jenis] ?? $row->jenis, 'jumlah' => 0, 'total' => 0];
            $perJenis[$row->jenis]['jumlah'] += (int) $row->jumlah;
            $perJenis[$row->jenis]['total'] += (int) $row->total;

            // total_kredit/total_debit (and therefore net/"Arus Kas Bersih")
            // only count jenis that actually move real cash in/out of the
            // pondok - a transfer antar santri or a tagihan/kantin payment
            // from saldo nets to zero for the pondok itself, so counting
            // them here would make "Arus Kas Bersih" swing on money that
            // never left the building. per_jenis above still lists every
            // jenis in full - this exclusion is only for the two totals.
            if (in_array($row->jenis, self::JENIS_KAS_MASUK, true) && $row->arah === Transaksi::ARAH_KREDIT) {
                $totalKredit += (int) $row->total;
            } elseif (in_array($row->jenis, self::JENIS_KAS_KELUAR, true) && $row->arah === Transaksi::ARAH_DEBIT) {
                $totalDebit += (int) $row->total;
            }
        }

        $barisTabungan = TransaksiTabungan::query()
            ->selectRaw('jenis, kanal, count(*) as jumlah, sum(nominal) as total')
            ->where('status', Transaksi::STATUS_BERHASIL)
            ->whereBetween('created_at', [$dari, $sampai])
            ->when($lembagaId, fn ($q) => $q->whereHas('rekening.santri', fn ($q) => $q->where('lembaga_id', $lembagaId)))
            ->groupBy('jenis', 'kanal')
            ->get();

        foreach ($barisTabungan as $row) {
            $perJenis[$row->jenis] ??= [
                'jenis' => $row->jenis,
                'label' => self::JENIS_TRANSAKSI_LABEL[$row->jenis] ?? $row->jenis,
                'jumlah' => 0,
                'total' => 0,
            ];
            $perJenis[$row->jenis]['jumlah'] += (int) $row->jumlah;
            $perJenis[$row->jenis]['total'] += (int) $row->total;

            if (in_array($row->jenis, [
                TransaksiTabungan::JENIS_SETORAN_TUNAI,
                TransaksiTabungan::JENIS_SETORAN_MIDTRANS,
            ], true)) {
                $totalKredit += (int) $row->total;
            }
        }

        return [
            'total_kredit' => $totalKredit,
            'total_debit' => $totalDebit,
            'net' => $totalKredit - $totalDebit,
            'per_jenis' => array_values($perJenis),
        ];
    }

    /**
     * nominal_sebelum_diskon is only ever populated when a diskon actually
     * applied (see TagihanService::hitungDiskon) - for tagihan without a
     * diskon it's null, so "sebelum diskon" for those rows is just their
     * own nominal (nothing was discounted off it).
     */
    private function ringkasanTagihan(Carbon $dari, Carbon $sampai, ?int $lembagaId): array
    {
        $rows = Tagihan::query()
            ->selectRaw('jenis_tagihan_id, count(*) as jumlah, sum(nominal) as nominal, sum(nominal_terbayar) as terbayar, sum(coalesce(nominal_sebelum_diskon, nominal)) as sebelum_diskon, count(distinct case when nominal_terbayar > 0 then santri_id end) as santri_bayar')
            ->whereBetween('tagihans.created_at', [$dari, $sampai])
            ->when($lembagaId, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where('lembaga_id', $lembagaId)))
            ->groupBy('jenis_tagihan_id')
            ->with('jenisTagihan')
            ->get();

        $perJenis = $rows->map(fn ($row) => [
            'nama' => $row->jenisTagihan?->nama ?? '(dihapus)',
            'jumlah' => (int) $row->jumlah,
            'sebelum_diskon' => (int) $row->sebelum_diskon,
            'diskon' => (int) $row->sebelum_diskon - (int) $row->nominal,
            'nominal' => (int) $row->nominal,
            'terbayar' => (int) $row->terbayar,
            'sisa' => (int) $row->nominal - (int) $row->terbayar,
            'santri_bayar' => (int) $row->santri_bayar,
        ])->values()->all();

        $totalSebelumDiskon = (int) $rows->sum('sebelum_diskon');
        $totalNominal = (int) $rows->sum('nominal');

        // Counted separately (not summed from per_jenis) so a santri with
        // tagihan in multiple jenis isn't counted more than once here.
        $totalSantriBayar = (int) Tagihan::query()
            ->whereBetween('tagihans.created_at', [$dari, $sampai])
            ->when($lembagaId, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where('lembaga_id', $lembagaId)))
            ->where('nominal_terbayar', '>', 0)
            ->distinct('santri_id')
            ->count('santri_id');

        return [
            'total_sebelum_diskon' => $totalSebelumDiskon,
            'total_diskon' => $totalSebelumDiskon - $totalNominal,
            'total_nominal' => $totalNominal,
            'total_terbayar' => (int) $rows->sum('terbayar'),
            'total_sisa' => (int) $rows->sum(fn ($row) => $row->nominal - $row->terbayar),
            'total_santri_bayar' => $totalSantriBayar,
            'per_jenis' => $perJenis,
        ];
    }

    private function ringkasanTopupWali(Carbon $dari, Carbon $sampai, ?int $lembagaId): array
    {
        $query = TopupWali::query()
            ->where('status', TopupWali::STATUS_PAID)
            ->whereBetween('paid_at', [$dari, $sampai])
            ->when($lembagaId, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where('lembaga_id', $lembagaId)));

        $totalKeTabungan = (int) (clone $query)
            ->where('tujuan', TopupWali::TUJUAN_TABUNGAN)
            ->sum('nominal_diminta');

        return [
            'jumlah' => (int) $query->count(),
            'total_diminta' => (int) $query->sum('nominal_diminta'),
            'total_ke_tagihan' => (int) $query->sum('nominal_potongan_tagihan'),
            'total_ke_saldo' => (int) $query->sum('nominal_ke_saldo'),
            'total_ke_tabungan' => $totalKeTabungan,
        ];
    }

    private function ringkasanPenarikan(Carbon $dari, Carbon $sampai, ?int $lembagaId): array
    {
        $query = PenarikanRequest::query()
            ->where('status', PenarikanRequest::STATUS_SELESAI)
            ->whereBetween('diproses_at', [$dari, $sampai])
            ->when($lembagaId, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where('lembaga_id', $lembagaId)));

        return [
            'jumlah' => (int) $query->count(),
            'total' => (int) $query->sum('nominal_diminta'),
        ];
    }

    public function jenisTransaksiLabel(string $jenis): string
    {
        return self::JENIS_TRANSAKSI_LABEL[$jenis] ?? $jenis;
    }

    /**
     * The full jenis => label map, for views that just need a lookup table
     * (e.g. `$label[$tx->jenis] ?? $tx->jenis` in a Blade table) rather than
     * a single lookup - callers should prefer this over hand-rolling their
     * own subset list, so a newly added Transaksi::JENIS_* constant doesn't
     * silently fall back to its raw snake_case value anywhere it's shown.
     *
     * @return array<string, string>
     */
    public function semuaJenisLabel(): array
    {
        return self::JENIS_TRANSAKSI_LABEL;
    }
}

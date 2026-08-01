<?php

namespace App\Services;

use App\Models\Device;
use App\Models\MutasiKas;
use App\Models\RekeningTabungan;
use App\Models\Santri;
use App\Models\SesiKas;
use App\Models\Tagihan;
use App\Models\Transaksi;
use App\Models\TransaksiTabungan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class TabunganService
{
    public function __construct(
        private WalletService $wallet,
        private SaldoFloorService $batasSaldo,
        private SesiKasService $sesiKas,
        private PushNotificationService $push,
    ) {}

    public function saldoBisaDitabung(Santri $santri): int
    {
        $saldo = (int) ($santri->saldo?->saldo ?? 0);
        $tagihanJatuhTempo = $santri->tagihans()
            ->whereIn('status', [Tagihan::STATUS_BELUM_LUNAS, Tagihan::STATUS_SEBAGIAN])
            ->whereDate('jatuh_tempo', '<=', today())
            ->get()
            ->sum(fn (Tagihan $tagihan) => $tagihan->sisa());

        return max(0, $saldo - $this->batasSaldo->minimal() - $tagihanJatuhTempo);
    }

    public function setorTunai(
        Santri $santri,
        int $nominal,
        SesiKas $sesi,
        User $petugas,
        string $idempotencyKey,
        ?string $catatan = null,
    ): TransaksiTabungan {
        $this->validasiNominal($nominal);

        return DB::transaction(function () use ($santri, $nominal, $sesi, $petugas, $idempotencyKey, $catatan) {
            if ($lama = $this->cariIdempotensi($idempotencyKey)) {
                return $lama;
            }

            $transaksi = $this->kredit(
                $santri,
                $nominal,
                TransaksiTabungan::JENIS_SETORAN_TUNAI,
                TransaksiTabungan::KANAL_PETUGAS,
                $idempotencyKey,
                [
                    'diproses_oleh' => $petugas->id,
                    'sesi_kas_id' => $sesi->id,
                    'catatan' => $catatan,
                ],
            );

            $this->sesiKas->catatMutasi(
                $sesi,
                MutasiKas::ARAH_MASUK,
                MutasiKas::KATEGORI_SETORAN_TABUNGAN,
                $nominal,
                $petugas,
                'tabungan:'.$idempotencyKey,
                $transaksi,
                "Setoran tunai tabungan {$santri->nama}",
            );

            DB::afterCommit(fn () => $this->notifySetoranTunai($santri, $transaksi, $nominal));

            return $transaksi;
        });
    }

    public function pindahDariSaldo(
        Santri $santri,
        int $nominal,
        ?User $pelaku,
        string $kanal,
        string $idempotencyKey,
        ?Device $device = null,
    ): TransaksiTabungan {
        $this->validasiNominal($nominal);

        return DB::transaction(function () use ($santri, $nominal, $pelaku, $kanal, $idempotencyKey, $device) {
            if ($lama = $this->cariIdempotensi($idempotencyKey)) {
                return $lama;
            }

            $this->wallet->lockSaldo($santri);
            if ($nominal > $this->saldoBisaDitabung($santri->fresh())) {
                throw new RuntimeException('Saldo yang dapat dipindahkan tidak mencukupi setelah menyisakan saldo minimum dan tagihan jatuh tempo.');
            }

            $transferUuid = (string) Str::uuid();
            $transaksiSaldo = $this->wallet->debit($santri, $nominal, Transaksi::JENIS_TRANSFER_KE_TABUNGAN, [
                'metode' => Transaksi::METODE_SISTEM,
                'diproses_oleh' => $pelaku?->id,
                'idempotency_key' => 'saldo:'.$idempotencyKey,
                'metadata' => ['transfer_uuid' => $transferUuid, 'kanal' => $kanal],
            ]);

            return $this->kredit(
                $santri,
                $nominal,
                TransaksiTabungan::JENIS_SETORAN_DARI_SALDO,
                $kanal,
                $idempotencyKey,
                [
                    'transfer_uuid' => $transferUuid,
                    'referensi_type' => $transaksiSaldo::class,
                    'referensi_id' => $transaksiSaldo->id,
                    'diproses_oleh' => $pelaku?->id,
                    'device_id' => $device?->id,
                ],
            );
        });
    }

    public function kreditMidtrans(
        Santri $santri,
        int $nominal,
        string $idempotencyKey,
        mixed $referensi,
        ?User $wali = null,
    ): TransaksiTabungan {
        $this->validasiNominal($nominal);

        return DB::transaction(function () use ($santri, $nominal, $idempotencyKey, $referensi, $wali) {
            if ($lama = $this->cariIdempotensi($idempotencyKey)) {
                return $lama;
            }

            return $this->kredit(
                $santri,
                $nominal,
                TransaksiTabungan::JENIS_SETORAN_MIDTRANS,
                TransaksiTabungan::KANAL_MIDTRANS,
                $idempotencyKey,
                [
                    'referensi_type' => $referensi::class,
                    'referensi_id' => $referensi->getKey(),
                    'diproses_oleh' => $wali?->id,
                ],
            );
        });
    }

    private function kredit(
        Santri $santri,
        int $nominal,
        string $jenis,
        string $kanal,
        string $idempotencyKey,
        array $atribut = [],
    ): TransaksiTabungan {
        $rekening = RekeningTabungan::query()->lockForUpdate()->firstOrCreate(
            ['santri_id' => $santri->id],
            ['saldo' => 0, 'status' => RekeningTabungan::STATUS_AKTIF, 'dibuka_at' => now()],
        );

        if ($rekening->status !== RekeningTabungan::STATUS_AKTIF) {
            throw new RuntimeException('Rekening tabungan sedang dibekukan.');
        }

        $sebelum = (int) $rekening->saldo;
        $sesudah = $sebelum + $nominal;
        $rekening->update(['saldo' => $sesudah]);

        return TransaksiTabungan::create(array_merge([
            'rekening_tabungan_id' => $rekening->id,
            'jenis' => $jenis,
            'kanal' => $kanal,
            'arah' => TransaksiTabungan::ARAH_KREDIT,
            'nominal' => $nominal,
            'saldo_sebelum' => $sebelum,
            'saldo_sesudah' => $sesudah,
            'status' => Transaksi::STATUS_BERHASIL,
            'idempotency_key' => $idempotencyKey,
        ], $atribut));
    }

    private function cariIdempotensi(string $kunci): ?TransaksiTabungan
    {
        return TransaksiTabungan::query()->where('idempotency_key', $kunci)->first();
    }

    private function notifySetoranTunai(Santri $santri, TransaksiTabungan $transaksi, int $nominal): void
    {
        $body = "Tabungan {$santri->nama} bertambah Rp".number_format($nominal, 0, ',', '.')
            .' melalui setoran tunai petugas kios. Saldo tabungan: Rp'
            .number_format((int) $transaksi->saldo_sesudah, 0, ',', '.').'.';

        foreach ($santri->walis as $wali) {
            // TransaksiTabungan bukan Transaksi saldo. Jangan kirim
            // transaksi_id agar aplikasi tidak membuka endpoint detail yang
            // salah; layar detail tetap menampilkan isi notifikasi lengkap.
            $this->push->notify($wali, 'Setoran Tabungan Berhasil', $body, [
                'type' => 'setoran_tabungan_tunai',
                'santri_id' => $santri->id,
                'santri_nama' => $santri->nama,
                'tabungan_transaksi_id' => $transaksi->id,
                'saldo_tabungan' => (int) $transaksi->saldo_sesudah,
            ]);
        }
    }

    private function validasiNominal(int $nominal): void
    {
        if ($nominal <= 0) {
            throw new InvalidArgumentException('Nominal tabungan harus lebih besar dari 0.');
        }

        if ($nominal > $this->batasSaldo->maksimalNominal()) {
            throw new InvalidArgumentException('Nominal melebihi batas maksimum transaksi.');
        }
    }
}

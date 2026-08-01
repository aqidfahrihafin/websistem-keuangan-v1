<?php

namespace App\Services;

use App\Models\Device;
use App\Models\MutasiKas;
use App\Models\SesiKas;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class SesiKasService
{
    public function buka(
        User $petugas,
        string $lokasi,
        int $saldoAwal,
        ?Device $device = null,
        ?string $catatan = null,
    ): SesiKas {
        if ($saldoAwal < 0) {
            throw new InvalidArgumentException('Saldo awal kas tidak boleh negatif.');
        }

        if (trim($lokasi) === '') {
            throw new InvalidArgumentException('Lokasi kas wajib diisi.');
        }

        return DB::transaction(function () use ($petugas, $lokasi, $saldoAwal, $device, $catatan) {
            if (! $device) {
                throw new InvalidArgumentException('Perangkat kios wajib dipilih.');
            }

            $device = Device::query()->lockForUpdate()->findOrFail($device->id);

            if ($device->status !== 'aktif') {
                throw new RuntimeException('Perangkat kios sedang nonaktif.');
            }

            if (! $device->petugasTerdaftar()->where('users.id', $petugas->id)->wherePivot('aktif', true)->exists()) {
                throw new RuntimeException('Petugas tidak ditugaskan pada perangkat kios ini.');
            }

            if ($device->sesi_kas_aktif_id) {
                $pemegang = SesiKas::query()->with('petugas')->find($device->sesi_kas_aktif_id);

                throw new RuntimeException(
                    $pemegang
                        ? "Perangkat masih memiliki sesi aktif milik {$pemegang->petugas->name} sejak "
                            .$pemegang->dibuka_at->format('d/m/Y H:i').'.'
                        : 'Perangkat masih terkunci oleh sesi kas aktif. Hubungi administrator.'
                );
            }

            // Pemeriksaan ini menjaga kompatibilitas bila data lama belum memiliki penunjuk sesi.
            $sesiPerangkat = SesiKas::query()
                ->where('device_id', $device->id)
                ->where('status', SesiKas::STATUS_AKTIF)
                ->lockForUpdate()
                ->with('petugas')
                ->first();

            if ($sesiPerangkat) {
                throw new RuntimeException(
                    "Perangkat masih memiliki sesi aktif milik {$sesiPerangkat->petugas->name} sejak "
                    .$sesiPerangkat->dibuka_at->format('d/m/Y H:i').'.'
                );
            }

            $sesiBelumSelesai = SesiKas::query()
                ->where('petugas_id', $petugas->id)
                ->whereIn('status', [
                    SesiKas::STATUS_AKTIF,
                    SesiKas::STATUS_MENUNGGU_VERIFIKASI,
                ])
                ->lockForUpdate()
                ->latest('dibuka_at')
                ->first();

            if ($sesiBelumSelesai) {
                throw new RuntimeException(
                    $sesiBelumSelesai->status === SesiKas::STATUS_MENUNGGU_VERIFIKASI
                        ? 'Sesi kas sebelumnya masih menunggu verifikasi admin. Anda belum dapat membuka sesi baru.'
                        : 'Petugas masih memiliki sesi kas yang aktif.'
                );
            }

            $sesi = SesiKas::create([
                'nomor' => 'KAS-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(5)),
                'petugas_id' => $petugas->id,
                'device_id' => $device->id,
                'lokasi' => trim($lokasi),
                'saldo_awal' => $saldoAwal,
                'total_masuk' => 0,
                'total_keluar' => 0,
                'saldo_seharusnya' => $saldoAwal,
                'status' => SesiKas::STATUS_AKTIF,
                'catatan_pembukaan' => $catatan,
                'dibuka_at' => now(),
            ]);

            $device->update([
                'sesi_kas_aktif_id' => $sesi->id,
                'petugas_jaga_id' => $petugas->id,
                'petugas_jaga_sejak' => now(),
            ]);

            return $sesi;
        });
    }

    public function ambilSesiAktif(User $petugas, array $deviceIds): SesiKas
    {
        $deviceIds = collect($deviceIds)
            ->filter(fn ($deviceId) => $deviceId !== null && $deviceId !== '')
            ->values()
            ->all();

        if ($deviceIds === []) {
            throw new RuntimeException('Anda belum ditugaskan pada perangkat kios. Semua transaksi dinonaktifkan.');
        }

        $sesi = SesiKas::query()
            ->where('petugas_id', $petugas->id)
            ->whereIn('device_id', $deviceIds)
            ->where('status', SesiKas::STATUS_AKTIF)
            ->with('device')
            ->orderByDesc('dibuka_at')
            ->orderByDesc('id')
            ->first();

        if (! $sesi) {
            throw new RuntimeException('Tidak ada sesi kas aktif yang sah untuk petugas ini.');
        }

        $sesiAktifPerangkat = SesiKas::query()
            ->where('device_id', $sesi->device_id)
            ->where('status', SesiKas::STATUS_AKTIF)
            ->orderByDesc('dibuka_at')
            ->orderByDesc('id')
            ->first();

        $pointerValid = $sesi->device?->sesi_kas_aktif_id === $sesi->id
            || $sesi->device?->sesi_kas_aktif_id === null;

        if ($sesiAktifPerangkat?->id === $sesi->id || $pointerValid) {
            return $sesi;
        }

        throw new RuntimeException('Tidak ada sesi kas aktif yang sah untuk petugas ini.');
    }

    public function tutup(SesiKas $sesi, User $petugas, int $uangFisik, ?string $catatan = null): SesiKas
    {
        if ($uangFisik < 0) {
            throw new InvalidArgumentException('Jumlah uang fisik tidak boleh negatif.');
        }

        return DB::transaction(function () use ($sesi, $petugas, $uangFisik, $catatan) {
            $sesi = SesiKas::query()->lockForUpdate()->findOrFail($sesi->id);

            if ($sesi->petugas_id !== $petugas->id) {
                throw new RuntimeException('Sesi kas hanya dapat ditutup oleh petugas yang membukanya.');
            }

            if ($sesi->status !== SesiKas::STATUS_AKTIF) {
                throw new RuntimeException('Sesi kas ini sudah ditutup.');
            }

            $sesi->update([
                'uang_fisik_akhir' => $uangFisik,
                'selisih' => $uangFisik - $sesi->saldo_seharusnya,
                'status' => SesiKas::STATUS_MENUNGGU_VERIFIKASI,
                'catatan_penutupan' => $catatan,
                'ditutup_at' => now(),
            ]);

            if ($sesi->device_id) {
                Device::whereKey($sesi->device_id)
                    ->where('sesi_kas_aktif_id', $sesi->id)
                    ->update([
                        'sesi_kas_aktif_id' => null,
                        'petugas_jaga_id' => null,
                        'petugas_jaga_sejak' => null,
                    ]);
            }

            return $sesi->fresh();
        });
    }

    public function verifikasi(SesiKas $sesi, User $pemeriksa): SesiKas
    {
        return DB::transaction(function () use ($sesi, $pemeriksa) {
            $sesi = SesiKas::query()->lockForUpdate()->findOrFail($sesi->id);

            if ($sesi->status !== SesiKas::STATUS_MENUNGGU_VERIFIKASI) {
                throw new RuntimeException('Sesi kas belum siap diverifikasi.');
            }

            $sesi->update([
                'status' => $sesi->selisih === 0 ? SesiKas::STATUS_SESUAI : SesiKas::STATUS_SELISIH,
                'diverifikasi_oleh' => $pemeriksa->id,
                'diverifikasi_at' => now(),
            ]);

            return $sesi->fresh();
        });
    }

    public function catatMutasi(
    SesiKas $sesi,
    string $arah,
    string $kategori,
    int $nominal,
    User $petugas,
    string $idempotencyKey,
    mixed $referensi = null,
    ?string $keterangan = null,
): MutasiKas {
    if ($nominal <= 0) {
        throw new InvalidArgumentException('Nominal mutasi kas harus lebih besar dari 0.');
    }

    return DB::transaction(function () use ($sesi, $arah, $kategori, $nominal, $petugas, $idempotencyKey, $referensi, $keterangan) {
        $sesi = SesiKas::query()->lockForUpdate()->with('device')->findOrFail($sesi->id);

        if ($sesi->status !== SesiKas::STATUS_AKTIF || $sesi->petugas_id !== $petugas->id) {
            throw new RuntimeException('Mutasi hanya dapat dicatat pada sesi kas aktif milik petugas.');
        }

        $sesiAktifPerangkat = SesiKas::query()
            ->where('device_id', $sesi->device_id)
            ->where('status', SesiKas::STATUS_AKTIF)
            ->orderByDesc('dibuka_at')
            ->orderByDesc('id')
            ->first();

        $pointerValid = $sesi->device?->sesi_kas_aktif_id === $sesi->id
            || $sesi->device?->sesi_kas_aktif_id === null;

        if ($sesiAktifPerangkat?->id !== $sesi->id && ! $pointerValid) {
            throw new RuntimeException('Mutasi hanya dapat dicatat pada sesi kas aktif milik petugas.');
        }

        $lama = MutasiKas::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($lama) {
            return $lama;
        }

        if (! in_array($arah, [MutasiKas::ARAH_MASUK, MutasiKas::ARAH_KELUAR], true)) {
            throw new InvalidArgumentException('Arah mutasi kas tidak valid.');
        }

        if ($arah === MutasiKas::ARAH_KELUAR && $nominal > $sesi->saldo_seharusnya) {
            throw new RuntimeException('Kas sesi tidak mencukupi untuk transaksi keluar ini.');
        }

        $mutasi = MutasiKas::create([
            'sesi_kas_id' => $sesi->id,
            'arah' => $arah,
            'kategori' => $kategori,
            'nominal' => $nominal,
            'referensi_type' => $referensi ? $referensi::class : null,
            'referensi_id' => $referensi?->getKey(),
            'diproses_oleh' => $petugas->id,
            'idempotency_key' => $idempotencyKey,
            'keterangan' => $keterangan,
        ]);

        $masuk = $arah === MutasiKas::ARAH_MASUK ? $nominal : 0;
        $keluar = $arah === MutasiKas::ARAH_KELUAR ? $nominal : 0;
        $sesi->update([
            'total_masuk' => $sesi->total_masuk + $masuk,
            'total_keluar' => $sesi->total_keluar + $keluar,
            'saldo_seharusnya' => $sesi->saldo_seharusnya + $masuk - $keluar,
        ]);

        return $mutasi;
    });
}
}

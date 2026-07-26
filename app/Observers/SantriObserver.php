<?php

namespace App\Observers;

use App\Models\KartuSantri;
use App\Models\Keluarga;
use App\Models\SaldoSantri;
use App\Models\Santri;
use App\Services\KategoriDiskonService;
use App\Services\KeluargaLinkingService;

class SantriObserver
{
    public function __construct(
        private KeluargaLinkingService $linking,
        private KategoriDiskonService $kategoriDiskon,
    ) {}

    public function created(Santri $santri): void
    {
        SaldoSantri::firstOrCreate(['santri_id' => $santri->id], ['saldo' => 0]);
        $this->linking->syncForSantri($santri);
        $this->kategoriDiskon->syncBersaudaraForKeluarga($santri->keluarga_id);
    }

    public function updated(Santri $santri): void
    {
        if ($santri->wasChanged('keluarga_id')) {
            $originalKeluargaId = $santri->getOriginal('keluarga_id');
            $previousNoKk = $originalKeluargaId ? Keluarga::find($originalKeluargaId)?->no_kk : null;

            $this->linking->syncForSantri($santri, $previousNoKk);

            $this->kategoriDiskon->syncBersaudaraForKeluarga($originalKeluargaId);
            $this->kategoriDiskon->syncBersaudaraForKeluarga($santri->keluarga_id);
        }

        if ($santri->wasChanged('status')) {
            $this->kategoriDiskon->syncBersaudaraForKeluarga($santri->keluarga_id);

            if ($santri->getOriginal('status') === Santri::STATUS_BARU && $santri->status === Santri::STATUS_AKTIF) {
                $this->kategoriDiskon->assignSantriBaruKategoriJikaAktivasi($santri);
            }

            if (in_array($santri->status, [Santri::STATUS_NONAKTIF, Santri::STATUS_LULUS, Santri::STATUS_KELUAR], true)) {
                // Query-builder update (not $kartu->update()) - safe here since
                // KartuSantri has no observer of its own, but kept consistent
                // with the rest of this observer's style.
                KartuSantri::where('santri_id', $santri->id)
                    ->where('status', KartuSantri::STATUS_AKTIF)
                    ->update([
                        'status' => KartuSantri::STATUS_NONAKTIF,
                        'dinonaktifkan_at' => now(),
                        'alasan_nonaktif' => "Otomatis: status santri berubah menjadi \"{$santri->status}\".",
                    ]);
            }
        }
    }
}

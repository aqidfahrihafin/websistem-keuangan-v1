<?php

namespace App\Services;

use App\Models\KategoriDiskon;
use App\Models\Santri;

class KategoriDiskonService
{
    /**
     * Recompute the auto-assigned "santri bersaudara" category for every
     * aktif santri in the given keluarga: assigned when the keluarga has 2+
     * aktif santri, cleared when it drops back below that. Never touches a
     * santri whose kategori_diskon was set manually by an admin
     * (kategori_diskon_auto = false) - that always takes precedence.
     */
    public function syncBersaudaraForKeluarga(?int $keluargaId): void
    {
        if (! $keluargaId) {
            return;
        }

        $bersaudara = KategoriDiskon::where('kode', KategoriDiskon::KODE_BERSAUDARA)->first();

        if (! $bersaudara) {
            return;
        }

        $santris = Santri::where('keluarga_id', $keluargaId)
            ->where('status', Santri::STATUS_AKTIF)
            ->get();

        if ($santris->count() >= 2) {
            foreach ($santris as $santri) {
                if ($santri->kategori_diskon_id === null || $santri->kategori_diskon_auto) {
                    $santri->update([
                        'kategori_diskon_id' => $bersaudara->id,
                        'kategori_diskon_auto' => true,
                    ]);
                }
            }

            return;
        }

        foreach ($santris as $santri) {
            if ($santri->kategori_diskon_auto && $santri->kategori_diskon_id === $bersaudara->id) {
                $santri->update([
                    'kategori_diskon_id' => null,
                    'kategori_diskon_auto' => false,
                ]);
            }
        }
    }

    /**
     * One-off courtesy assignment fired when a santri is verified/activated
     * (status baru -> aktif): gives them the "santri baru" discount kategori
     * if one is configured. Unlike syncBersaudaraForKeluarga this never
     * un-assigns anything later - admin removes it manually whenever the
     * pondok considers the "new student" period over, no automatic expiry.
     * Same override-safe rule as bersaudara: never touches a manually-set
     * kategori.
     */
    public function assignSantriBaruKategoriJikaAktivasi(Santri $santri): void
    {
        if ($santri->kategori_diskon_id !== null && ! $santri->kategori_diskon_auto) {
            return;
        }

        $santriBaru = KategoriDiskon::where('kode', KategoriDiskon::KODE_SANTRI_BARU)->first();

        if (! $santriBaru) {
            return;
        }

        // A plain query-builder update (not $santri->update()) is intentional:
        // $santri is the same instance still mid-save inside the observer's
        // "updated" event for the status change, whose original attributes
        // haven't been synced yet. Calling ->update() on it here would make
        // "status" look dirty again on the nested save, re-firing the
        // "updated" event and recursing forever. A query-builder update
        // writes the columns directly without touching model state or
        // firing events at all - which is also all this needs to do.
        Santri::whereKey($santri->id)->update([
            'kategori_diskon_id' => $santriBaru->id,
            'kategori_diskon_auto' => true,
        ]);

        $santri->kategori_diskon_id = $santriBaru->id;
        $santri->kategori_diskon_auto = true;
    }
}

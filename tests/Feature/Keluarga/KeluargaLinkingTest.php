<?php

use App\Models\Keluarga;
use App\Models\Santri;
use App\Models\WaliSantri;
use App\Services\KeluargaLinkingService;

it('automatically links a wali to every santri sharing the same No. KK', function () {
    $keluarga = Keluarga::factory()->create(['no_kk' => '1111222233334444']);
    $wali = makeUserWithRole('wali', ['no_kk' => $keluarga->no_kk]);

    $santriA = Santri::factory()->create(['keluarga_id' => $keluarga->id]);

    expect($wali->anakAsuh()->pluck('santris.id'))->toContain($santriA->id);

    $santriB = Santri::factory()->create(['keluarga_id' => $keluarga->id]);

    expect($wali->anakAsuh()->pluck('santris.id'))
        ->toContain($santriA->id)
        ->toContain($santriB->id)
        ->toHaveCount(2);
});

it('preserves manually-linked wali-santri pairs when auto-sync runs again', function () {
    $keluargaA = Keluarga::factory()->create();
    $keluargaB = Keluarga::factory()->create();

    $wali = makeUserWithRole('wali', ['no_kk' => $keluargaA->no_kk]);
    $santriSameKk = Santri::factory()->create(['keluarga_id' => $keluargaA->id]);
    $santriOtherKk = Santri::factory()->create(['keluarga_id' => $keluargaB->id]);

    // Manual override link for a santri outside the wali's own KK.
    WaliSantri::create([
        'user_id' => $wali->id,
        'santri_id' => $santriOtherKk->id,
        'hubungan' => 'kerabat',
        'is_auto_generated' => false,
        'is_primary' => false,
    ]);

    // Trigger another auto-sync pass (e.g. wali profile re-saved).
    app(KeluargaLinkingService::class)->syncForUser($wali->fresh());

    $links = $wali->anakAsuh()->pluck('santris.id');

    expect($links)->toContain($santriSameKk->id)
        ->toContain($santriOtherKk->id)
        ->toHaveCount(2);
});

it('removes an auto-generated link when a santri moves to a different keluarga', function () {
    $keluargaA = Keluarga::factory()->create();
    $keluargaB = Keluarga::factory()->create();

    $wali = makeUserWithRole('wali', ['no_kk' => $keluargaA->no_kk]);
    $santri = Santri::factory()->create(['keluarga_id' => $keluargaA->id]);

    expect($wali->anakAsuh()->pluck('santris.id'))->toContain($santri->id);

    $santri->update(['keluarga_id' => $keluargaB->id]);

    expect($wali->anakAsuh()->pluck('santris.id'))->not->toContain($santri->id);
});

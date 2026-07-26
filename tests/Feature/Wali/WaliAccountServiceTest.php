<?php

use App\Models\Keluarga;
use App\Models\Santri;
use App\Models\User;
use App\Services\WaliAccountService;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

it('creates a wali account with the No. KK as both login identifier and initial password', function () {
    Role::findOrCreate('wali', 'web');
    $keluarga = Keluarga::factory()->create(['no_kk' => '8888888888888888']);
    $santri = Santri::factory()->create(['keluarga_id' => $keluarga->id]);

    $wali = app(WaliAccountService::class)->buatAkunDenganNoKkSebagaiSandi($keluarga, 'Bapak Contoh');

    expect($wali->no_kk)->toBe('8888888888888888')
        ->and($wali->must_change_password)->toBeTrue()
        ->and(Hash::check('8888888888888888', $wali->password))->toBeTrue()
        ->and($wali->hasRole('wali'))->toBeTrue()
        ->and($wali->anakAsuh()->pluck('santris.id'))->toContain($santri->id);
});

it('reports whether a wali account already exists for a No. KK', function () {
    Role::findOrCreate('wali', 'web');
    $service = app(WaliAccountService::class);

    expect($service->adaWaliUntuk('7777777788889999'))->toBeFalse();

    User::factory()->create(['no_kk' => '7777777788889999'])->assignRole('wali');

    expect($service->adaWaliUntuk('7777777788889999'))->toBeTrue();
});

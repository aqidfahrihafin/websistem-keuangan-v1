<?php

use App\Exceptions\InvalidTransaksiException;
use App\Models\UnitUsaha;
use App\Models\User;
use App\Services\PengelolaAccountService;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

it('creates a pengelola account, assigns the role, and links it to the unit usaha', function () {
    Role::findOrCreate('pengelola', 'web');
    $unit = UnitUsaha::factory()->create();
    $service = app(PengelolaAccountService::class);

    $result = $service->buatAkunDenganSandiAcak($unit, 'Budi Santoso', 'budi@kantin.test', '08123456789');

    expect($result['user'])->toBeInstanceOf(User::class)
        ->and($result['user']->hasRole('pengelola'))->toBeTrue()
        ->and($result['user']->must_change_password)->toBeTrue()
        ->and($result['password'])->not->toBeEmpty()
        ->and(Hash::check($result['password'], $result['user']->fresh()->password))->toBeTrue()
        ->and($unit->fresh()->pengelola_user_id)->toBe($result['user']->id);
});

it('refuses to create a second pengelola account for a unit that already has one', function () {
    Role::findOrCreate('pengelola', 'web');
    $unit = UnitUsaha::factory()->create();
    $service = app(PengelolaAccountService::class);

    $service->buatAkunDenganSandiAcak($unit, 'Budi Santoso', 'budi@kantin.test');

    expect(fn () => $service->buatAkunDenganSandiAcak($unit->fresh(), 'Orang Lain', 'lain@kantin.test'))
        ->toThrow(InvalidTransaksiException::class);

    expect(User::where('email', 'lain@kantin.test')->exists())->toBeFalse();
});

<?php

use App\Models\UnitUsaha;
use App\Models\User;
use App\Services\PengelolaAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // RefreshDatabase wipes the roles/permissions tables between tests,
        // but Spatie's registrar cache is process-lifetime - without this,
        // later tests see stale (or missing) roles from earlier tests.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function makeUserWithRole(string $role, array $attributes = []): User
{
    Role::findOrCreate($role, 'web');

    $user = User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}

/**
 * @return array{0: UnitUsaha, 1: User}
 */
function buatPengelola(): array
{
    Role::findOrCreate('pengelola', 'web');
    $unit = UnitUsaha::factory()->create();
    $result = app(PengelolaAccountService::class)->buatAkunDenganSandiAcak(
        $unit,
        'Pengelola '.$unit->kode,
        strtolower($unit->kode).'@kantin.test',
    );

    // The generated account is must_change_password=true by design (see
    // PengelolaAccountService) - most callers just need role/area access
    // exercised, not the separate forced-password-change gate (covered by
    // EnsurePasswordIsChangedTest), so it's cleared here to avoid every
    // request redirecting to /profil instead.
    $result['user']->update(['must_change_password' => false]);

    return [$unit->fresh(), $result['user']->fresh()];
}

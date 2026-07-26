<?php

use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('logs in staff using their email address', function () {
    $user = makeUserWithRole('admin', ['email' => 'staff@pesantren.test', 'password' => 'password']);

    Livewire::test('auth.login-form')
        ->set('login', 'staff@pesantren.test')
        ->set('password', 'password')
        ->call('submit');

    $this->assertAuthenticatedAs($user->fresh());
});

it('logs in a santri using their NIS instead of an email', function () {
    $user = makeUserWithRole('santri', ['email' => null, 'nis' => '1234567890', 'password' => 'password']);

    Livewire::test('auth.login-form')
        ->set('login', '1234567890')
        ->set('password', 'password')
        ->call('submit');

    $this->assertAuthenticatedAs($user->fresh());
});

it('logs in a wali using their No. KK as the login identifier', function () {
    Role::findOrCreate('wali', 'web');
    $wali = User::factory()->create(['no_kk' => '1234567890123456', 'password' => '1234567890123456'])->assignRole('wali');

    Livewire::test('auth.login-form')
        ->set('login', '1234567890123456')
        ->set('password', '1234567890123456')
        ->call('submit');

    $this->assertAuthenticatedAs($wali->fresh());
});

it('refuses No. KK login when more than one account shares that No. KK', function () {
    Role::findOrCreate('wali', 'web');
    User::factory()->create(['no_kk' => '9999999999999999', 'password' => 'password'])->assignRole('wali');
    User::factory()->create(['no_kk' => '9999999999999999', 'password' => 'password'])->assignRole('wali');

    Livewire::test('auth.login-form')
        ->set('login', '9999999999999999')
        ->set('password', 'password')
        ->call('submit')
        ->assertHasErrors('login');

    $this->assertGuest();
});

it('rejects invalid credentials without authenticating', function () {
    makeUserWithRole('admin', ['email' => 'staff@pesantren.test', 'password' => 'password']);

    Livewire::test('auth.login-form')
        ->set('login', 'staff@pesantren.test')
        ->set('password', 'wrong-password')
        ->call('submit')
        ->assertHasErrors('login');

    $this->assertGuest();
});

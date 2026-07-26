<?php

use App\Livewire\Profil\Index as ProfilIndex;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('redirects a guest to login when visiting the profile page', function () {
    $this->get('/profil')->assertRedirect('/login');
});

it('lets any authenticated role view and update their own profile', function (string $role) {
    $user = makeUserWithRole($role);

    Livewire::actingAs($user)->test(ProfilIndex::class)
        ->assertSet('name', $user->name)
        ->assertSet('email', $user->email)
        ->set('name', 'Nama Baru')
        ->set('phone', '081234567890')
        ->call('simpanProfil')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Nama Baru')
        ->and($user->fresh()->phone)->toBe('081234567890');
})->with(['admin', 'bendahara', 'pengasuh', 'wali', 'santri', 'dev']);

it('rejects an email already used by another user', function () {
    User::factory()->create(['email' => 'terpakai@pesantren.test']);
    $user = makeUserWithRole('admin');

    Livewire::actingAs($user)->test(ProfilIndex::class)
        ->set('email', 'terpakai@pesantren.test')
        ->call('simpanProfil')
        ->assertHasErrors(['email']);
});

it('changes the password when the current password is correct and confirmation matches', function () {
    $user = makeUserWithRole('admin', ['password' => Hash::make('kata-sandi-lama')]);

    Livewire::actingAs($user)->test(ProfilIndex::class)
        ->set('current_password', 'kata-sandi-lama')
        ->set('password', 'kata-sandi-baru')
        ->set('password_confirmation', 'kata-sandi-baru')
        ->call('simpanPassword')
        ->assertHasNoErrors();

    expect(Hash::check('kata-sandi-baru', $user->fresh()->password))->toBeTrue();
});

it('rejects a password change with the wrong current password or a mismatched confirmation', function () {
    $user = makeUserWithRole('admin', ['password' => Hash::make('kata-sandi-lama')]);

    Livewire::actingAs($user)->test(ProfilIndex::class)
        ->set('current_password', 'salah')
        ->set('password', 'kata-sandi-baru')
        ->set('password_confirmation', 'kata-sandi-baru')
        ->call('simpanPassword')
        ->assertHasErrors(['current_password']);

    Livewire::actingAs($user)->test(ProfilIndex::class)
        ->set('current_password', 'kata-sandi-lama')
        ->set('password', 'kata-sandi-baru')
        ->set('password_confirmation', 'tidak-cocok')
        ->call('simpanPassword')
        ->assertHasErrors(['password']);

    expect(Hash::check('kata-sandi-lama', $user->fresh()->password))->toBeTrue();
});

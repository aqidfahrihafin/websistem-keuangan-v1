<?php

use App\Livewire\Profil\Index as ProfilIndex;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('redirects a user who must change their password away from any other page', function () {
    Role::findOrCreate('wali', 'web');
    $wali = User::factory()->create(['must_change_password' => true])->assignRole('wali');

    $this->actingAs($wali)->get('/wali')->assertRedirect(route('profil'));
});

it('still lets a user who must change their password reach the profil page itself', function () {
    Role::findOrCreate('wali', 'web');
    $wali = User::factory()->create(['must_change_password' => true])->assignRole('wali');

    $this->actingAs($wali)->get('/profil')->assertOk();
});

it('does not restrict a user who does not need to change their password', function () {
    $admin = makeUserWithRole('admin', ['must_change_password' => false]);

    $this->actingAs($admin)->get('/admin')->assertOk();
});

it('clears the must_change_password flag once the password is actually changed', function () {
    $wali = makeUserWithRole('wali', ['must_change_password' => true, 'password' => Hash::make('sandi-lama')]);

    Livewire::actingAs($wali)->test(ProfilIndex::class)
        ->set('current_password', 'sandi-lama')
        ->set('password', 'sandi-baru-123')
        ->set('password_confirmation', 'sandi-baru-123')
        ->call('simpanPassword')
        ->assertHasNoErrors();

    expect($wali->fresh()->must_change_password)->toBeFalse();
});

/*
 * Regression coverage for a real bug: Livewire::test()->call(...) drives the
 * component directly, in-process, without going through the actual HTTP
 * kernel/middleware stack - so the test above kept passing even while the
 * middleware was silently redirecting every real Livewire AJAX request
 * (including the password-change submission itself) back to /profil in the
 * browser. Livewire's update endpoint doesn't live at a fixed "livewire/*"
 * path (see EnsurePasswordIsChanged::isLivewireRequest() for why), so
 * these tests hit the real registered route to prove the fix actually works
 * end to end.
 */
it('does not redirect a genuine Livewire AJAX request away, even when must_change_password is true', function () {
    Role::findOrCreate('wali', 'web');
    $wali = User::factory()->create(['must_change_password' => true])->assignRole('wali');

    $response = $this->actingAs($wali)
        ->withHeaders(['X-Livewire' => 'true'])
        ->postJson(route('default-livewire.update'), []);

    expect($response->status())->not->toBe(302);
});

it('still redirects a request to the real Livewire update route when it is not actually from the Livewire client', function () {
    Role::findOrCreate('wali', 'web');
    $wali = User::factory()->create(['must_change_password' => true])->assignRole('wali');

    $this->actingAs($wali)
        ->post(route('default-livewire.update'), [])
        ->assertRedirect(route('profil'));
});

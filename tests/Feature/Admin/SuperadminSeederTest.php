<?php

use App\Models\User;
use Database\Seeders\SuperadminSeeder;
use Illuminate\Support\Facades\Hash;

it('creates an idempotent superadmin without overwriting an existing password', function () {
    config()->set('superadmin', [
        'name' => 'Pemilik Sistem',
        'email' => 'owner@example.test',
        'password' => 'rahasia-superadmin-aman',
    ]);

    $this->seed(SuperadminSeeder::class);

    $user = User::where('email', 'owner@example.test')->firstOrFail();
    expect($user->name)->toBe('Pemilik Sistem')
        ->and($user->hasAllRoles(['superadmin', 'admin']))->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and(Hash::check('rahasia-superadmin-aman', $user->password))->toBeTrue();

    $originalPassword = $user->password;
    config()->set('superadmin.password', 'kata-sandi-baru-tidak-dipakai');

    $this->seed(SuperadminSeeder::class);

    expect(User::where('email', 'owner@example.test')->count())->toBe(1)
        ->and($user->fresh()->password)->toBe($originalPassword);
});

it('refuses to create a superadmin with incomplete credentials', function () {
    config()->set('superadmin.email', '');
    config()->set('superadmin.password', '');

    $this->seed(SuperadminSeeder::class);
})->throws(RuntimeException::class, 'SUPERADMIN_EMAIL wajib diisi');

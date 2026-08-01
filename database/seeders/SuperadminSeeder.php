<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $name = trim((string) config('superadmin.name', 'Superadmin Sistem'));
        $email = trim((string) config('superadmin.email'));
        $password = (string) config('superadmin.password');

        if ($email === '') {
            throw new RuntimeException(
                'SUPERADMIN_EMAIL wajib diisi sebelum menjalankan SuperadminSeeder.'
            );
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('SUPERADMIN_EMAIL harus berupa alamat email yang valid.');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            if (mb_strlen($password) < 12) {
                throw new RuntimeException(
                    'SUPERADMIN_PASSWORD minimal 12 karakter untuk membuat akun baru.'
                );
            }

            $user = User::create([
                'name' => $name !== '' ? $name : 'Superadmin Sistem',
                'email' => $email,
                'password' => Hash::make($password),
                'must_change_password' => true,
            ]);
        }

        Role::findOrCreate('superadmin', 'web');
        Role::findOrCreate('admin', 'web');

        // Keep any legitimate existing role while guaranteeing access to
        // both the system-control and operational admin areas.
        $user->assignRole(['superadmin', 'admin']);

        $this->command?->info("Superadmin siap digunakan: {$user->email}");
    }
}

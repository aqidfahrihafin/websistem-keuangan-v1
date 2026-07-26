<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@pesantren.test'],
            ['name' => 'Admin Sistem', 'password' => 'password']
        );
        $admin->syncRoles(['admin']);

        $bendahara = User::firstOrCreate(
            ['email' => 'bendahara@pesantren.test'],
            ['name' => 'Bendahara Pondok', 'password' => 'password']
        );
        $bendahara->syncRoles(['bendahara']);

        $pengasuh = User::firstOrCreate(
            ['email' => 'pengasuh@pesantren.test'],
            ['name' => 'Pengasuh Pondok', 'password' => 'password']
        );
        $pengasuh->syncRoles(['pengasuh']);

        $dev = User::firstOrCreate(
            ['email' => 'dev@pesantren.test'],
            ['name' => 'Developer', 'password' => 'password']
        );
        $dev->syncRoles(['dev']);
    }
}

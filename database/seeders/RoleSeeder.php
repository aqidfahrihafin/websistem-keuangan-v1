<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin', 'bendahara', 'admin_lembaga', 'admin_rayon', 'petugas_kios', 'pengasuh', 'wali', 'santri', 'pengelola', 'dev'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}

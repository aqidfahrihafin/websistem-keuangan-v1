<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin', 'bendahara', 'pengasuh', 'wali', 'santri', 'pengelola', 'dev'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}

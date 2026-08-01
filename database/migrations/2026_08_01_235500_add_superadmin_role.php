<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate('superadmin', 'web');

        // Bootstrap safely on an existing installation. The oldest admin is
        // the original system owner in this application and keeps `admin` as
        // a secondary role so deployments never lose access during rollout.
        // Use the relation directly instead of User::role('admin'): on a
        // brand-new database migrations run before RoleSeeder, so the scope
        // would throw RoleDoesNotExist while there is simply no owner yet.
        $owner = User::query()
            ->whereHas('roles', fn ($query) => $query
                ->where('name', 'admin')
                ->where('guard_name', 'web'))
            ->orderBy('id')
            ->first();
        if ($owner && ! $owner->hasRole($role)) {
            $owner->assignRole($role);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = Role::findByName('superadmin', 'web');
        $role->users()->detach();
        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

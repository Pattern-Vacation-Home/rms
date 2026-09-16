<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['model_has_roles', 'model_has_permissions'] as $table) {
            if (Schema::hasTable($table) && Schema::getColumnType($table, 'model_id') !== 'string') {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->uuid('model_id')->change());
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $modules = ['dashboard', 'units', 'owners', 'tenants', 'bookings', 'tasks', 'inspections', 'accounting', 'agents', 'maintainers', 'administration'];
        $permissions = collect($modules)->flatMap(fn (string $module) => [$module.'.view', $module.'.manage'])
            ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        $superAdmin = Role::firstOrCreate(['name' => 'Super Administrator', 'guard_name' => 'web']);
        $superAdmin->syncPermissions($permissions);
        User::where('role', 'admin')->each(fn (User $user) => $user->syncRoles([$superAdmin]));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Access-control data is intentionally preserved to avoid locking out administrators.
    }
};

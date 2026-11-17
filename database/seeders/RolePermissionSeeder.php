<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Rbac\RbacDefaults;
use Illuminate\Database\Seeder;

/**
 * Seeder idempotent untuk role dan permission. Aman dijalankan berulang.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionIds = [];

        foreach (RbacDefaults::permissions() as $permission) {
            $model = Permission::query()->updateOrCreate(
                ['name' => $permission['name']],
                ['label' => $permission['label'], 'group' => $permission['group']],
            );

            $permissionIds[$permission['name']] = $model->getKey();
        }

        foreach (RbacDefaults::roles() as $name => $definition) {
            $role = Role::query()->updateOrCreate(
                ['name' => $name],
                ['label' => $definition['label'], 'description' => $definition['description'], 'is_system' => true],
            );

            $ids = array_values(array_filter(array_map(
                fn (string $permission) => $permissionIds[$permission] ?? null,
                $definition['permissions'],
            )));

            $role->permissions()->sync($ids);
        }
    }
}

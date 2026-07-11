<?php

namespace Database\Seeders;

use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as PermissionRole;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $contentModules = ['pages', 'programs', 'events', 'posts', 'galleries', 'team'];
        $permissions = [];

        foreach ($contentModules as $module) {
            foreach (['view', 'create', 'update', 'delete', 'publish'] as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }
        array_push($permissions,
            'menus.manage', 'settings.manage', 'media.manage', 'contacts.manage',
            'users.manage', 'invites.manage', 'activity.view', 'content.restore', 'content.force-delete',
        );

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = PermissionRole::findOrCreate(Role::SuperAdmin->value, 'web');
        $admin = PermissionRole::findOrCreate(Role::Admin->value, 'web');
        $editor = PermissionRole::findOrCreate(Role::Editor->value, 'web');
        $superAdmin->syncPermissions($permissions);
        $admin->syncPermissions(array_values(array_filter($permissions, fn (string $permission) => ! in_array($permission, [
            'content.restore',
            'content.force-delete',
        ], true))));
        $editor->syncPermissions(array_values(array_filter($permissions, fn (string $permission) => preg_match('/^(pages|programs|events|posts|galleries|team)\.(view|create|update|delete|publish)$/', $permission) === 1
            || $permission === 'media.manage')));
    }
}

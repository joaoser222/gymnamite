<?php

namespace App\Console\Commands;

use App\AccessControl\AccessModule;
use App\AccessControl\AccessRole;
use App\AccessControl\RolePermissionMap;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('access-control:sync {--default-role=administrator : Role assigned to users without a role} {--without-users : Do not assign a default role to users}')]
#[Description('Synchronize access control roles, permissions, role permissions, and optionally users without a role')]
class SyncAccessControl extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $defaultRole = (string) $this->option('default-role');
        $assignUsers = ! (bool) $this->option('without-users');

        if ($assignUsers && AccessRole::tryFrom($defaultRole) === null) {
            $this->error("Invalid default role [{$defaultRole}].");

            return self::FAILURE;
        }

        DB::transaction(function () use ($assignUsers, $defaultRole): void {
            $roles = $this->syncRoles();
            $permissions = $this->syncPermissions();
            $this->syncRolePermissions($roles, $permissions);

            if ($assignUsers) {
                $this->assignDefaultRoleToUsers($roles[$defaultRole]);
            }
        });

        $this->components->info('Access control synchronized successfully.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, Role>
     */
    private function syncRoles(): array
    {
        $roles = [];

        foreach (AccessRole::cases() as $accessRole) {
            $roles[$accessRole->value] = Role::query()->updateOrCreate(
                ['name' => $accessRole->value],
                ['description' => $accessRole->label()],
            );
        }

        $this->components->twoColumnDetail('Roles', (string) count($roles));

        return $roles;
    }

    /**
     * @return array<string, Permission>
     */
    private function syncPermissions(): array
    {
        $permissions = [];

        foreach (AccessModule::cases() as $module) {
            foreach ($module->actions() as $action) {
                $permissionName = $module->value.'.'.$action->value;

                $permissions[$permissionName] = Permission::query()->updateOrCreate(
                    ['name' => $permissionName],
                    ['description' => $module->label().' - '.$action->label()],
                );
            }
        }

        $this->components->twoColumnDetail('Permissions', (string) count($permissions));

        return $permissions;
    }

    /**
     * @param  array<string, Role>  $roles
     * @param  array<string, Permission>  $permissions
     */
    private function syncRolePermissions(array $roles, array $permissions): void
    {
        $rolePermissionMap = (new RolePermissionMap)->getMap();

        foreach ($rolePermissionMap as $roleName => $modulePermissions) {
            $permissionIds = [];

            foreach ($modulePermissions as $module => $actions) {
                foreach ($actions as $action) {
                    $permissionName = $module.'.'.$action;

                    if (isset($permissions[$permissionName])) {
                        $permissionIds[] = $permissions[$permissionName]->id;
                    }
                }
            }

            $roles[$roleName]->permissions()->sync($permissionIds);
        }

        $this->components->twoColumnDetail('Role permissions', 'synced');
    }

    private function assignDefaultRoleToUsers(Role $defaultRole): void
    {
        $updatedUsers = User::query()
            ->whereNull('role_id')
            ->update(['role_id' => $defaultRole->id]);

        $this->components->twoColumnDetail('Users updated', (string) $updatedUsers);
    }
}

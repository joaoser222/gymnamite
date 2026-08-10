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

#[Signature('access-control:sync {--default-role=administrator : Role assigned to users without a role} {--without-users : Do not assign a default role to users} {--reset-role-permissions : Restore role permissions from RolePermissionMap}')]
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
        $resetRolePermissions = (bool) $this->option('reset-role-permissions');

        if ($assignUsers && AccessRole::tryFrom($defaultRole) === null) {
            $this->error("Invalid default role [{$defaultRole}].");

            return self::FAILURE;
        }

        DB::transaction(function () use ($assignUsers, $defaultRole, $resetRolePermissions): void {
            $roles = $this->syncRoles();
            $permissions = $this->syncPermissions();
            $this->syncRolePermissions($roles, $permissions, $resetRolePermissions);

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
    private function syncRolePermissions(array $roles, array $permissions, bool $resetRolePermissions): void
    {
        $rolePermissionMap = (new RolePermissionMap)->getMap();

        foreach ($rolePermissionMap as $roleName => $modulePermissions) {
            if (! $resetRolePermissions && ! $roles[$roleName]->wasRecentlyCreated) {
                continue;
            }

            $permissionIds = [];

            foreach ($modulePermissions as $module => $actions) {
                foreach ($actions as $action) {
                    $permissionName = $module.'.'.$action;

                    if (isset($permissions[$permissionName])) {
                        $permissionIds[] = $permissions[$permissionName]->id;
                    }
                }
            }

            $role = $roles[$roleName];
            $previousPermissionIds = $role->permissions()->pluck('permissions.id')->all();
            $addedPermissionIds = array_values(array_diff($permissionIds, $previousPermissionIds));
            $removedPermissionIds = array_values(array_diff($previousPermissionIds, $permissionIds));

            $role->permissions()->sync($permissionIds);
            $this->syncUsersForRole($role, $addedPermissionIds, $removedPermissionIds);
        }

        $this->components->twoColumnDetail('Role permissions', $resetRolePermissions ? 'reset' : 'initialized');
    }

    private function assignDefaultRoleToUsers(Role $defaultRole): void
    {
        $users = User::query()
            ->whereNull('role_id')
            ->get();

        $users->each(function (User $user) use ($defaultRole): void {
            $user->update(['role_id' => $defaultRole->id]);
            $user->permissions()->sync($defaultRole->permissions()->pluck('permissions.id')->all());
        });

        $this->components->twoColumnDetail('Users updated', (string) $users->count());
    }

    /**
     * @param  array<int, int>  $addedPermissionIds
     * @param  array<int, int>  $removedPermissionIds
     */
    private function syncUsersForRole(Role $role, array $addedPermissionIds, array $removedPermissionIds): void
    {
        User::query()
            ->where('role_id', $role->id)
            ->eachById(function (User $user) use ($addedPermissionIds, $removedPermissionIds): void {
                if ($addedPermissionIds !== []) {
                    $user->permissions()->syncWithoutDetaching($addedPermissionIds);
                }

                if ($removedPermissionIds !== []) {
                    $user->permissions()->detach($removedPermissionIds);
                }
            });
    }
}

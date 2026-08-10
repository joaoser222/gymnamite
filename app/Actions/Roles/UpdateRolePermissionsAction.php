<?php

namespace App\Actions\Roles;

use App\AccessControl\AccessRole;
use App\Actions\BaseAction;
use App\DTOs\Roles\UpdateRolePermissionsDTO;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateRolePermissionsAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Role::class;

    protected function handle(mixed $input): Role
    {
        if (! $input instanceof UpdateRolePermissionsDTO) {
            throw new \InvalidArgumentException('UpdateRolePermissionsAction requires an UpdateRolePermissionsDTO.');
        }

        $role = Role::query()->with('permissions:id')->findOrFail($input->role_id);
        $accessRole = AccessRole::tryFrom($role->name);

        if ($accessRole === null) {
            throw ValidationException::withMessages([
                'role' => 'O perfil informado não é configurável.',
            ]);
        }

        if ($accessRole === AccessRole::ADMINISTRATOR) {
            throw ValidationException::withMessages([
                'role' => 'As permissões do administrador não podem ser alteradas.',
            ]);
        }

        $previousPermissionIds = $role->permissions->pluck('id')->map(static fn (int $id): int => $id)->all();
        $permissionIds = collect($input->permission_ids)
            ->map(static fn (int $id): int => $id)
            ->unique()
            ->values()
            ->all();

        $addedPermissionIds = array_values(array_diff($permissionIds, $previousPermissionIds));
        $removedPermissionIds = array_values(array_diff($previousPermissionIds, $permissionIds));

        $role->permissions()->sync($permissionIds);

        $this->syncUsers($role, $addedPermissionIds, $removedPermissionIds);

        return $role->unsetRelation('permissions')->load('permissions');
    }

    /**
     * @param  array<int, int>  $addedPermissionIds
     * @param  array<int, int>  $removedPermissionIds
     */
    private function syncUsers(Role $role, array $addedPermissionIds, array $removedPermissionIds): void
    {
        $userIds = User::query()->where('role_id', $role->id)->pluck('id')->all();

        if ($userIds === []) {
            return;
        }

        if ($addedPermissionIds !== []) {
            $timestamp = now();
            $rows = [];

            foreach ($userIds as $userId) {
                foreach ($addedPermissionIds as $permissionId) {
                    $rows[] = [
                        'user_id' => $userId,
                        'permission_id' => $permissionId,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
            }

            DB::table('permission_user')->insertOrIgnore($rows);
        }

        if ($removedPermissionIds !== []) {
            DB::table('permission_user')
                ->whereIn('user_id', $userIds)
                ->whereIn('permission_id', $removedPermissionIds)
                ->delete();
        }
    }
}

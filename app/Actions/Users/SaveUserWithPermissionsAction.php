<?php

namespace App\Actions\Users;

use App\Actions\BaseAction;
use App\DTOs\Users\SaveUserWithPermissionsDTO;
use App\Models\Role;
use App\Models\User;

class SaveUserWithPermissionsAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = User::class;

    protected function handle(mixed $input): User
    {
        if (! $input instanceof SaveUserWithPermissionsDTO) {
            throw new \InvalidArgumentException('SaveUserWithPermissionsAction requires a SaveUserWithPermissionsDTO.');
        }

        $user = $input->id === null
            ? User::query()->create($input->userAttributes())
            : tap(User::query()->findOrFail($input->id), fn (User $user) => $user->update($input->userAttributes()));

        $user->permissions()->sync($this->allowedPermissionIds($input->role_id, $input->permission_ids));

        return $user;
    }

    /**
     * @param  array<int, int|string>  $selectedPermissionIds
     * @return array<int, int>
     */
    private function allowedPermissionIds(?int $roleId, array $selectedPermissionIds): array
    {
        if ($roleId === null) {
            return [];
        }

        $allowedPermissionIds = Role::query()
            ->find($roleId)
            ?->permissions()
            ->pluck('permissions.id')
            ->map(static fn (int $permissionId): int => $permissionId)
            ->all() ?? [];

        return collect($selectedPermissionIds)
            ->map(static fn (int|string $permissionId): int => (int) $permissionId)
            ->filter(fn (int $permissionId): bool => in_array($permissionId, $allowedPermissionIds, true))
            ->unique()
            ->values()
            ->all();
    }
}

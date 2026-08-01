<?php

namespace Tests\Concerns;

use App\Models\Permission;
use App\Models\User;

trait InteractsWithPermissions
{
    protected function grantPermission(User $user, string $permission): void
    {
        $user->permissions()->attach($this->createPermission($permission));
    }

    protected function createPermission(string $name): Permission
    {
        return Permission::query()->create([
            'name' => $name,
            'description' => $name,
        ]);
    }
}

<?php

namespace App\Traits;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

trait AuthorizesAccessControl
{
    abstract protected function accessModule(): AccessModule;

    protected function authorizeAccess(AccessAction $action): void
    {
        abort_unless($this->allowsAccess($action), Response::HTTP_FORBIDDEN);
    }

    protected function allowsAccess(AccessAction $action): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        $permissionName = $this->accessPermissionName($action);

        if ($user->permissions()
            ->where('name', $permissionName)
            ->exists()) {
            return true;
        }

        if ($user->role === null) {
            return false;
        }

        return $user->role
            ->permissions()
            ->where('name', $permissionName)
            ->exists();
    }

    protected function accessPermissionName(AccessAction $action): string
    {
        return $this->accessModule()->value.'.'.$action->value;
    }
}

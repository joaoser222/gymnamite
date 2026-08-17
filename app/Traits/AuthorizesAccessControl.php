<?php

namespace App\Traits;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use Illuminate\Support\Facades\Gate;

trait AuthorizesAccessControl
{
    abstract protected function accessModule(): AccessModule;

    protected function authorizeAccess(AccessAction $action): void
    {
        Gate::authorize($this->accessPermissionName($action));
    }

    protected function allowsAccess(AccessAction $action): bool
    {
        return Gate::allows($this->accessPermissionName($action));
    }

    protected function accessPermissionName(AccessAction $action): string
    {
        return $this->accessModule()->value.'.'.$action->value;
    }
}

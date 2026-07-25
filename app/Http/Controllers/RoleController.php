<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Models\Role;

class RoleController extends CrudModuleController
{
    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'description', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::USER;
    }

    protected function modelClass(): string
    {
        return Role::class;
    }
}

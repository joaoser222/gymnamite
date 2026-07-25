<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Models\PlanCategory;

class PlanCategoryController extends CrudModuleController
{
    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'created_at'];

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
        return AccessModule::PLAN_CATEGORY;
    }

    protected function modelClass(): string
    {
        return PlanCategory::class;
    }
}

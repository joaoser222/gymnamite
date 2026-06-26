<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Models\Plan;
use App\Traits\HasModule;

class PlanController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'modality_quantity', 'description', 'created_at'];

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
        return AccessModule::PLAN;
    }

    protected function modelClass(): string
    {
        return Plan::class;
    }
}

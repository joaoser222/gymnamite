<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Models\Report;

class ReportController extends ReadOnlyModuleController
{
    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'label', 'description', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name', 'label', 'description'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'label', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::REPORT;
    }

    protected function modelClass(): string
    {
        return Report::class;
    }
}

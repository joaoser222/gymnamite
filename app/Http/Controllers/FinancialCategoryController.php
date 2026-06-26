<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\OperationType;
use App\Models\FinancialCategory;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Model;

class FinancialCategoryController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'cost_center_name', 'color', 'created_at'];

    protected array $joins = ['costCenter'];

    /**
     * @var array<string, string>
     */
    protected array $fieldsMapping = [
        'id' => 'financial_categories.id',
        'name' => 'financial_categories.name',
        'color' => 'financial_categories.color',
        'created_at' => 'financial_categories.created_at',
        'cost_center_name' => 'cost_centers.name',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name', 'cost_center_name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'cost_center_name', 'created_at', 'updated_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::FINANCIAL_CATEGORY;
    }

    protected function modelClass(): string
    {
        return FinancialCategory::class;
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'operationTypes' => $this->enumOptions(OperationType::class),
            ],

        ];
    }
}

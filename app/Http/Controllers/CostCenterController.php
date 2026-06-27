<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\OperationType;
use App\Models\CostCenter;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CostCenterController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'color', 'operation_type', 'created_at'];

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
        return AccessModule::COST_CENTER;
    }

    protected function modelClass(): string
    {
        return CostCenter::class;
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'operationTypes' => $this->enumOptions(OperationType::class),
            ],
        ];
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

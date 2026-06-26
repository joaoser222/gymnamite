<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Models\Movement;
use App\Traits\HasModule;
use Illuminate\Http\Request;

class MovementController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'operation_type', 'movement_type', 'value', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['id'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::MOVEMENT;
    }

    protected function modelClass(): string
    {
        return Movement::class;
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'operationTypes' => $this->enumOptions(OperationType::class),
                'movementTypes' => $this->enumOptions(MovementType::class),
            ],
        ];
    }
}

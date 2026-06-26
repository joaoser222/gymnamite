<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\BillableStatus;
use App\Models\Contract;
use App\Traits\HasModule;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'plan_name', 'price', 'start_date', 'duration', 'status', 'created_at'];

    protected array $joins = ['client', 'plan'];

    /**
     * @var array<string, string>
     */
    protected array $fieldsMapping = [
        'id' => 'contracts.id',
        'plan_name' => 'contracts.plan_name',
        'price' => 'contracts.price',
        'start_date' => 'contracts.start_date',
        'duration' => 'contracts.duration',
        'status' => 'contracts.status',
        'created_at' => 'contracts.created_at',
        'client_name' => 'clients.name',
        'plan_name_rel' => 'plans.name',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['plan_name', 'client_name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'plan_name', 'start_date', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::CONTRACT;
    }

    protected function modelClass(): string
    {
        return Contract::class;
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'billableStatus' => $this->enumOptions(BillableStatus::class),
            ],
        ];
    }
}

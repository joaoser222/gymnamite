<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Models\GatewayCustomer;

class GatewayCustomerController extends ReadOnlyModuleController
{
    /**
     * @var array<int, string>
     */
    protected array $fields = [
        'id',
        'gateway_reference_key',
        'holder_type',
        'holder_id',
        'created_at',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['gateway_reference_key', 'holder_type'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'holder_type', 'holder_id', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::GATEWAY_CUSTOMER;
    }

    protected function modelClass(): string
    {
        return GatewayCustomer::class;
    }
}

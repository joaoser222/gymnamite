<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Models\GatewayCreditCard;

class GatewayCreditCardController extends ReadOnlyModuleController
{
    /**
     * @var array<int, string>
     */
    protected array $fields = [
        'id',
        'gateway_reference_key',
        'status',
        'card_brand',
        'last_digits',
        'created_at',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['gateway_reference_key', 'status', 'card_brand', 'last_digits'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'status', 'card_brand', 'last_digits', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::GATEWAY_CREDIT_CARD;
    }

    protected function modelClass(): string
    {
        return GatewayCreditCard::class;
    }
}

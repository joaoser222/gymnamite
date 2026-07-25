<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\Gateway\PostbackStatus;
use App\Models\GatewayPostback;
use App\Traits\HasReadOnlyModule;
use Illuminate\Http\Request;

class GatewayPostbackController extends Controller
{
    use HasReadOnlyModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = [
        'id',
        'postback_event',
        'postback_type',
        'status',
        'gateway_account_id',
        'created_at',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['postback_event', 'postback_type', 'status'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'postback_event', 'postback_type', 'status', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::GATEWAY_POSTBACK;
    }

    protected function modelClass(): string
    {
        return GatewayPostback::class;
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'postbackStatus' => $this->enumOptions(PostbackStatus::class),
            ],
        ];
    }
}

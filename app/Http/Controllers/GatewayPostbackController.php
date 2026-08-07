<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Actions\GatewayPostbacks\ProcessGatewayPostbackAction;
use App\DTOs\GatewayPostbacks\ProcessGatewayPostbackDTO;
use App\Enums\Gateway\PostbackStatus;
use App\Models\GatewayAccount;
use App\Models\GatewayPostback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GatewayPostbackController extends ReadOnlyModuleController
{
    private const AUTH_HEADER = 'asaas-access-token';

    public function __construct(
        private readonly ProcessGatewayPostbackAction $processGatewayPostback,
    ) {}

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

    public function receive(Request $request, GatewayAccount $gatewayAccount): JsonResponse
    {
        $settings = $gatewayAccount->settings ?? [];
        $token = is_array($settings) ? ($settings['webhook_token'] ?? null) : null;
        $receivedToken = $request->header(self::AUTH_HEADER);

        if (! is_string($token) || blank($token) || ! is_string($receivedToken) || ! hash_equals($token, $receivedToken)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $postback = $this->processGatewayPostback->execute(
            ProcessGatewayPostbackDTO::fromArray([
                'gateway_account_id' => $gatewayAccount->getKey(),
                'payload' => $request->all(),
            ]),
        );

        return response()->json([
            'id' => $postback->id,
            'status' => $postback->status instanceof PostbackStatus
                ? $postback->status->value
                : $postback->status,
        ], Response::HTTP_CREATED);
    }
}

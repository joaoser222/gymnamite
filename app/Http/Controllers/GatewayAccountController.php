<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Http\Requests\GatewayAccountRequest;
use App\Models\GatewayAccount;
use App\Services\Gateway\Definitions\GatewaySettingDefinition;
use App\Services\Gateway\GatewayManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GatewayAccountController extends CrudModuleController
{
    public function __construct(
        private readonly GatewayManager $gatewayManager,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'description', 'created_at', 'updated_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name', 'description'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::GATEWAY_ACCOUNT;
    }

    protected function modelClass(): string
    {
        return GatewayAccount::class;
    }

    protected function storeRequestClass(): ?string
    {
        return GatewayAccountRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return GatewayAccountRequest::class;
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'providers' => $this->gatewayManager->providers(),
            ],
        ];
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'providers' => $this->gatewayManager->providers(),
                'definitions' => $this->gatewayManager->all(),
            ],
        ];
    }

    public function show(Request $request): Response|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        $model = $this->modelFromRoute($request);

        if ($model instanceof GatewayAccount) {
            $model = $this->withoutSensitiveSettings($model);
        }

        if ($request->expectsJson()) {
            return response()->json($model);
        }

        $this->shareModuleRoutes();

        return Inertia::render($this->detailsComponent(), [
            $this->itemPropName() => $model,
            'id' => $model->getKey(),
            'routes' => $this->getModuleRoutes(),
            ...$this->moduleDetailsProps($model),
        ]);
    }

    private function withoutSensitiveSettings(GatewayAccount $gatewayAccount): GatewayAccount
    {
        $definition = $this->gatewayManager->find($gatewayAccount->name);

        if ($definition === null) {
            return $gatewayAccount;
        }

        $settings = $gatewayAccount->settings ?? [];

        foreach ($definition->settings() as $setting) {
            if ($setting instanceof GatewaySettingDefinition && $setting->type === 'password') {
                unset($settings[$setting->key]);
            }
        }

        $gatewayAccount->setAttribute('settings', $settings);

        return $gatewayAccount;
    }
}

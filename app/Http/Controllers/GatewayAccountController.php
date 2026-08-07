<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\GatewayAccounts\ConfigureFiscalDataAction;
use App\Actions\GatewayAccounts\CreateGatewayAccountAction;
use App\Actions\GatewayAccounts\UpdateGatewayAccountAction;
use App\DTOs\GatewayAccounts\ConfigureFiscalDataDTO;
use App\DTOs\GatewayAccounts\CreateGatewayAccountDTO;
use App\DTOs\GatewayAccounts\UpdateGatewayAccountDTO;
use App\Http\Requests\GatewayAccountRequest;
use App\Http\Requests\GatewayMunicipalConfigurationRequest;
use App\Models\GatewayAccount;
use App\PaymentGateways\Definitions\PaymentGatewaySettingDefinition;
use App\PaymentGateways\PaymentGatewayManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GatewayAccountController extends CrudModuleController
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly CreateGatewayAccountAction $createGatewayAccount,
        private readonly UpdateGatewayAccountAction $updateGatewayAccount,
        private readonly ConfigureFiscalDataAction $configureFiscalData,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'description', 'invoicing_enabled', 'invoicing_configured', 'created_at', 'updated_at'];

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

    protected function getModuleRoutes(): array
    {
        $routes = parent::getModuleRoutes();
        $parameter = ['gateway_account' => '__id__'];

        return [
            ...$routes,
            'municipalOptions' => str_replace(
                '__id__',
                ':id',
                route('gateway-accounts.invoicing.municipal-options', $parameter),
            ),
            'municipalServices' => str_replace(
                '__id__',
                ':id',
                route('gateway-accounts.invoicing.municipal-services', $parameter),
            ),
            'municipalConfiguration' => str_replace(
                '__id__',
                ':id',
                route('gateway-accounts.invoicing.municipal-configuration', $parameter),
            ),
        ];
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

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createGatewayAccount->execute(
            CreateGatewayAccountDTO::fromArray(
                $this->validatedRequestData($request, $this->storeRequestClass()),
            ),
        );

        if ($request->expectsJson()) {
            return response()->json($result->data, 201);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result->message,
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        /** @var GatewayAccount $gatewayAccount */
        $gatewayAccount = $this->modelFromRoute($request);

        $result = $this->updateGatewayAccount->execute(
            UpdateGatewayAccountDTO::fromArray([
                ...$this->validatedRequestData($request, $this->updateRequestClass()),
                'id' => $gatewayAccount->getKey(),
            ]),
        );

        if ($request->expectsJson()) {
            return response()->json($result->data);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result->message,
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    public function municipalOptions(GatewayAccount $gatewayAccount): JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        return response()->json(
            $this->gatewayManager->invoicingAdapter($gatewayAccount)->getMunicipalOptions(),
        );
    }

    public function municipalServices(Request $request, GatewayAccount $gatewayAccount): JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        return response()->json(
            $this->gatewayManager->invoicingAdapter($gatewayAccount)->getMunicipalServices(
                $request->only(['city', 'state', 'service_code', 'description']),
            ),
        );
    }

    public function configureFiscalData(GatewayMunicipalConfigurationRequest $request, GatewayAccount $gatewayAccount): JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        $result = $this->configureFiscalData->execute(
            ConfigureFiscalDataDTO::fromArray([
                ...$request->validated(),
                'id' => $gatewayAccount->getKey(),
            ]),
        );

        return response()->json($result->data);
    }

    private function withoutSensitiveSettings(GatewayAccount $gatewayAccount): GatewayAccount
    {
        $definition = $this->gatewayManager->find($gatewayAccount->name);

        if ($definition === null) {
            return $gatewayAccount;
        }

        $settings = $gatewayAccount->settings ?? [];

        foreach ($definition->settings() as $setting) {
            if ($setting instanceof PaymentGatewaySettingDefinition && $setting->type === 'password') {
                unset($settings[$setting->key]);
            }
        }

        if (isset($settings['invoicing']) && is_array($settings['invoicing'])) {
            foreach (['api_key', 'access_token', 'certificate', 'certificate_password', 'password', 'token'] as $key) {
                unset($settings['invoicing'][$key]);
            }
        }

        $gatewayAccount->setAttribute('settings', $settings);

        return $gatewayAccount;
    }
}

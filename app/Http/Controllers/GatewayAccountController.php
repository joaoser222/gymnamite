<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Http\Requests\GatewayAccountRequest;
use App\Http\Requests\GatewayMunicipalConfigurationRequest;
use App\Models\GatewayAccount;
use App\PaymentGateways\Definitions\PaymentGatewaySettingDefinition;
use App\PaymentGateways\PaymentGatewayManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GatewayAccountController extends CrudModuleController
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
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

        $definition = $this->gatewayManager->find((string) $gatewayAccount->name);

        if ($definition?->supportsInvoicing() !== true) {
            throw new ValidationException(
                Validator::make([], [
                    'gateway_account' => ['O provedor desta conta não suporta emissão fiscal.'],
                ]),
            );
        }

        $payload = array_filter([
            'municipalServiceCode' => $request->validated('municipal_service_code'),
            'municipalServiceName' => $request->validated('municipal_service_name'),
            'serviceDescription' => $request->validated('service_description'),
            'observations' => $request->validated('observations'),
            'incentivizedTax' => $request->validated('incentivized_tax'),
        ], static fn (mixed $value): bool => $value !== null);

        $response = $this->gatewayManager
            ->invoicingAdapter($gatewayAccount)
            ->configureFiscalData($payload);

        $settings = $gatewayAccount->settings ?? [];
        $settings['invoicing']['service_description'] = $request->validated('service_description');
        $settings['invoicing']['municipal_service_code'] = $request->validated('municipal_service_code');
        $settings['invoicing']['fiscal_configuration_at'] = now()->toISOString();
        $gatewayAccount->update(['settings' => $settings]);

        $safeResponse = array_intersect_key($response, array_flip([
            'municipalServiceCode',
            'municipalServiceName',
            'serviceDescription',
            'observations',
            'incentivizedTax',
            'status',
        ]));

        return response()->json([
            'configuration' => $safeResponse,
            'invoicing_configured' => $gatewayAccount->fresh()->invoicing_configured,
            'invoicing_supported' => $gatewayAccount->invoicing_supported,
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

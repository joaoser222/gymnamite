<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Contracts\CancelContractAction;
use App\Actions\Contracts\CreateContractAction;
use App\Actions\Contracts\FindClientAction;
use App\Actions\Contracts\UpdateContractAction;
use App\DTOs\Contracts\CancelContractDTO;
use App\DTOs\Contracts\CreateContractDTO;
use App\DTOs\Contracts\UpdateContractDTO;
use App\Enums\BillableStatus;
use App\Enums\GenderType;
use App\Enums\PaymentMethod;
use App\Http\Requests\ContractWizardRequest;
use App\Models\Contract;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\PlanTier;
use App\Models\Uf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends CrudModuleController
{
    public function __construct(
        private readonly CreateContractAction $createContract,
        private readonly UpdateContractAction $updateContract,
        private readonly FindClientAction $findClient,
        private readonly CancelContractAction $cancelContract,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'plan_name', 'total', 'first_due_date', 'installments', 'status', 'created_at'];

    protected array $joins = ['client'];

    /**
     * @var array<string, string>
     */
    protected array $fieldsMapping = [
        'id' => 'contracts.id',
        'plan_name' => 'contracts.plan_name',
        'total' => 'contracts.total',
        'first_due_date' => 'contracts.first_due_date',
        'installments' => 'contracts.installments',
        'status' => 'contracts.status',
        'created_at' => 'contracts.created_at',
        'client_name' => 'clients.name',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['plan_name', 'client_name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'plan_name', 'first_due_date', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::CONTRACT;
    }

    protected function modelClass(): string
    {
        return Contract::class;
    }

    public function create(): Response
    {
        $this->authorizeAccess(AccessAction::CREATE);

        return Inertia::render($this->detailsComponent(), [
            'routes' => [
                'index' => route('contracts.index'),
                'store' => route('contracts.store'),
                'update' => route('contracts.update', ['contract' => '__id__']),
                'create' => route('contracts.create'),
                'show' => route('contracts.show', ['contract' => '__id__']),
                'destroy' => route('contracts.destroy'),
                'changeVisibility' => route('contracts.change-visibility'),
                'findClient' => route('contracts.find-client'),
                'findCoupon' => route('contracts.find-coupon'),
            ],
            'options' => [
                'genderTypes' => $this->enumOptions(GenderType::class),
                'ufs' => $this->modelOptions(Uf::class),
                'plans' => $this->planOptions(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createContract->execute(
            CreateContractDTO::from(
                $this->validatedRequestData($request, ContractWizardRequest::class)
            )
        );

        if (! $result->success) {
            return back()->withErrors($result->errors ?? ['contract' => $result->message])->withInput();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result->message,
        ]);

        return redirect()->route('contracts.index');
    }

    public function findClient(Request $request): JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $document = preg_replace('/\D+/', '', (string) $request->query('document', ''));

        if ($document === '' || strlen($document) !== 11) {
            return response()->json(['client' => null]);
        }

        $result = $this->findClient->execute($document);

        return response()->json([
            'client' => $result->data,
        ]);
    }

    public function findCoupon(Request $request): JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $code = mb_strtoupper((string) $request->query('code', ''));

        if ($code === '') {
            return response()->json(['coupon' => null]);
        }

        /** @var Coupon|null $coupon */
        $coupon = Coupon::query()
            ->where('code', $code)
            ->where('visibility', 'visible')
            ->first();

        return response()->json([
            'coupon' => $coupon?->only([
                'id',
                'code',
                'percent',
                'discount_limit',
                'duration',
                'expiration_date',
            ]),
        ]);
    }

    public function cancel(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CANCEL);

        $contract = $this->modelFromRoute($request);

        $result = $this->cancelContract->execute(
            CancelContractDTO::from([
                ...$request->validate(['reason' => ['nullable', 'string', 'max:500']]),
                'contract_id' => $contract->getKey(),
            ])
        );

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $result->message,
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result->message,
        ]);

        return redirect()->route('contracts.index');
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        $contract = $this->modelFromRoute($request);

        $result = $this->updateContract->execute(
            UpdateContractDTO::from([
                ...$request->validate(['annotations' => ['nullable', 'string', 'max:500']]),
                'id' => $contract->getKey(),
            ])
        );

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json($contract->refresh());
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result->message,
        ]);

        return redirect()->route('contracts.index');
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    private function actionFailureResponse(Request $request, ?array $errors, ?string $message): RedirectResponse|JsonResponse
    {
        $message ??= 'Não foi possível concluir a operação.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => $errors,
            ], 422);
        }

        return back()->withErrors($errors ?? ['contract' => $message])->withInput();
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'billableStatus' => $this->enumOptions(BillableStatus::class),
            ],
        ];
    }

    public function show(Request $request): Response|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        $model = $this->modelFromRoute($request);

        if ($request->expectsJson()) {
            return response()->json($model);
        }

        $this->shareModuleRoutes();

        return Inertia::render($this->detailsComponent(), [
            $this->itemPropName() => $model,
            'id' => $model->getKey(),
            'routes' => [
                ...$this->getModuleRoutes(),
                'findClient' => route('contracts.find-client'),
                'findCoupon' => route('contracts.find-coupon'),
            ],
            ...$this->moduleDetailsProps($model),
        ]);
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        $clientInfo = null;
        $couponInfo = null;

        if ($model instanceof Contract) {
            $model->load(['client', 'coupon']);
            $clientInfo = $model->client
                ? "{$model->client->name} - {$model->client->document}"
                : null;
            $couponInfo = $model->coupon?->code;
        }

        return [
            'cancelRoute' => $model instanceof Contract
                ? route('contracts.cancel', ['contract' => $model])
                : null,
            'clientInfo' => $clientInfo,
            'couponInfo' => $couponInfo,
            'options' => [
                'billableStatus' => $this->enumOptions(BillableStatus::class),
                'paymentMethods' => $this->enumOptions(PaymentMethod::class),
                'plans' => $this->planOptions(),
                'genderTypes' => $this->enumOptions(GenderType::class),
                'ufs' => $this->modelOptions(Uf::class),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function planOptions(): array
    {
        return Plan::query()
            ->with(['tiers', 'planCategory'])
            ->where('visibility', 'visible')
            ->orderBy('name')
            ->get()
            ->map(function (Plan $plan): array {
                return [
                    'value' => $plan->id,
                    'title' => $plan->name,
                    'category' => $plan->planCategory?->name,
                    'modality_quantity' => $plan->modality_quantity,
                    'tiers' => $plan->tiers
                        ->sortBy('quantity')
                        ->map(fn (PlanTier $tier): array => [
                            'quantity' => $tier->quantity,
                            'price' => (float) $tier->price,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }
}

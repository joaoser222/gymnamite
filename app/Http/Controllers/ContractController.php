<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Enums\BillableStatus;
use App\Enums\GenderType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\ContractWizardRequest;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\PlanTier;
use App\Models\Uf;
use App\Services\BillingInvoiceService;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    use HasModule;

    public function __construct(
        private readonly BillingInvoiceService $billingInvoiceService,
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

    public function store(ContractWizardRequest $request): RedirectResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $validated = $request->validated();
        $generateInvoices = ! array_key_exists('generate_invoices', $validated)
            || (bool) $validated['generate_invoices'];

        /** @var Plan $plan */
        $plan = Plan::query()
            ->with(['tiers', 'planCategory'])
            ->where('visibility', 'visible')
            ->whereKey($validated['plan_id'])
            ->firstOrFail();

        /** @var PlanTier|null $tier */
        $tier = $plan->tiers->firstWhere('quantity', (int) $validated['installments']);

        if ($tier === null) {
            throw ValidationException::withMessages([
                'installments' => 'A duracao selecionada nao esta disponivel para este plano.',
            ]);
        }

        $coupon = null;

        if (! empty($validated['coupon_code'])) {
            /** @var Coupon|null $coupon */
            $coupon = Coupon::query()
                ->where('code', mb_strtoupper((string) $validated['coupon_code']))
                ->where('visibility', 'visible')
                ->first();

            if ($coupon === null) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'O cupom informado nao esta disponivel.',
                ]);
            }

            if ($coupon->expiration_date !== null && $coupon->expiration_date->isBefore(Date::today())) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'O cupom informado esta expirado.',
                ]);
            }

        }

        $grossValue = round((float) $tier->price * (int) $validated['installments'], 4);

        DB::transaction(function () use ($validated, $plan, $coupon, $grossValue, $generateInvoices): void {
            $clientData = Arr::only($validated, [
                'name',
                'email',
                'phone',
                'document',
                'gender',
                'birth_date',
                'legal_representative',
                'legal_representative_name',
                'legal_representative_document',
                'legal_representative_birth_date',
                'address_postal_code',
                'address',
                'address_number',
                'address_complement',
                'address_district',
                'address_state',
                'address_city',
            ]);

            /** @var Client $client */
            $client = isset($validated['client_id'])
                ? Client::query()->findOrFail($validated['client_id'])
                : new Client;

            if ($client->exists) {
                $client->update($clientData);
            } else {
                $client = Client::query()->create($clientData);
            }

            $contract = Contract::query()->create([
                'plan_name' => $plan->name,
                'modality_quantity' => (string) $plan->modality_quantity,
                'gross_value' => $grossValue,
                'discount_value' => 0,
                'total' => $grossValue,
                'payment_method' => PaymentMethod::CASH,
                'first_due_date' => Date::today()->format('Y-m-d'),
                'installments' => (int) $validated['installments'],
                'accepted_terms' => $generateInvoices ? 'accepted' : 'pending',
                'annotations' => $validated['annotations'] ?? null,
                'coupon_id' => $coupon?->id,
                'plan_id' => $plan->id,
                'client_id' => $client->id,
                'visibility' => 'visible',
            ]);

            if ($generateInvoices) {
                $invoices = $this->billingInvoiceService->generate($contract);
                $discountTotal = round($invoices->sum('discount_value'), 4);

                $this->queueGatewayInvoiceSync($invoices);

                $contract->update([
                    'discount_value' => $discountTotal,
                    'total' => round($grossValue - $discountTotal, 4),
                ]);

                return;
            }

            $grossInstallments = $this->splitAmount($grossValue, (int) $validated['installments']);
            $discountTotal = round(array_sum($this->billingInvoiceService->resolveDiscountInstallments(
                $contract->loadMissing('coupon'),
                $grossInstallments,
            )), 4);

            $contract->update([
                'discount_value' => $discountTotal,
                'total' => round($grossValue - $discountTotal, 4),
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Contrato criado com sucesso.',
        ]);

        return redirect()->route('contracts.index');
    }

    private function queueGatewayInvoiceSync(Collection $invoices): void
    {
        $invoicesToSync = $invoices->filter(
            fn ($invoice): bool => $invoice->shouldGenerateGatewayTransaction(),
        );

        if ($invoicesToSync->isEmpty()) {
            return;
        }

        Artisan::queue('gateway:sync-invoices', [
            '--invoice' => $invoicesToSync->modelKeys(),
        ])->afterCommit();
    }

    public function findClient(Request $request): JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $document = preg_replace('/\D+/', '', (string) $request->query('document', ''));

        if ($document === '' || strlen($document) !== 11) {
            return response()->json(['client' => null]);
        }

        /** @var Client|null $client */
        $client = Client::query()
            ->where('document', $document)
            ->first();

        return response()->json([
            'client' => $client?->only([
                'id',
                'name',
                'email',
                'phone',
                'document',
                'gender',
                'birth_date',
                'legal_representative',
                'legal_representative_name',
                'legal_representative_document',
                'legal_representative_birth_date',
                'address_postal_code',
                'address',
                'address_number',
                'address_complement',
                'address_district',
                'address_state',
                'address_city',
                'status',
            ]),
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

        /** @var Contract $contract */
        $contract = $this->modelFromRoute($request);

        DB::transaction(function () use ($contract): void {
            $contract->update([
                'status' => BillableStatus::CANCELED,
            ]);

            $contract->loadMissing('invoices');

            $contract->invoices()
                ->where('status', '!=', InvoiceStatus::PAID->value)
                ->update(['status' => InvoiceStatus::CANCELED->value]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Contrato cancelado com sucesso.',
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Contrato cancelado com sucesso.',
        ]);

        return redirect()->route('contracts.index');
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
     * @return array<int, array{value: string, label: string}>
     */
    private function modelOptions(string $modelClass): array
    {
        return $modelClass::query()
            ->select(['code', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Model $model): array => [
                'value' => (string) $model->getAttribute('code'),
                'label' => (string) $model->getAttribute('name'),
            ])
            ->all();
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

    /**
     * @return array<int, float>
     */
    private function splitAmount(float $amount, int $installments): array
    {
        $scale = 10000;
        $total = (int) round($amount * $scale);
        $baseInstallmentValue = intdiv($total, $installments);
        $remainder = $total % $installments;
        $parts = [];

        for ($index = 0; $index < $installments; $index++) {
            $parts[] = ($baseInstallmentValue + ($index < $remainder ? 1 : 0)) / $scale;
        }

        return $parts;
    }
}

<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Enums\BillableStatus;
use App\Enums\GenderType;
use App\Enums\PaymentMethod;
use App\Http\Requests\ContractWizardRequest;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Plan;
use App\Models\PlanTier;
use App\Models\Uf;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    use HasModule;

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

        return Inertia::render('contracts/Create', [
            'routes' => [
                'index' => route('contracts.index'),
                'store' => route('contracts.store'),
                'findClient' => route('contracts.find-client'),
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

        DB::transaction(function () use ($validated, $plan, $tier): void {
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

            Contract::query()->create([
                'plan_name' => $plan->name,
                'modality_quantity' => (string) $plan->modality_quantity,
                'gross_value' => $tier->price,
                'discount_value' => 0,
                'total' => $tier->price,
                'payment_method' => PaymentMethod::CASH,
                'first_due_date' => null,
                'installments' => (int) $validated['installments'],
                'accepted_terms' => 'accepted',
                'annotations' => $validated['annotations'] ?? null,
                'plan_id' => $plan->id,
                'plan_category_id' => $plan->plan_category_id,
                'client_id' => $client->id,
                'visibility' => 'visible',
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Contrato criado com sucesso.',
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

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'billableStatus' => $this->enumOptions(BillableStatus::class),
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
}

<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Enums\BillableStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\PurchaseRequest;
use App\Models\Purchase;
use App\Services\BillableItemService;
use App\Services\StockRecalculationService;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    use HasModule;

    public function __construct(
        private readonly BillableItemService $billableItemService,
        private readonly StockRecalculationService $stockRecalculationService,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'total', 'status', 'payment_method', 'created_at'];

    protected array $joins = ['supplier'];

    /**
     * @var array<string, string>
     */
    protected array $fieldsMapping = [
        'id' => 'purchases.id',
        'total' => 'purchases.total',
        'status' => 'purchases.status',
        'payment_method' => 'purchases.payment_method',
        'created_at' => 'purchases.created_at',
        'supplier_name' => 'suppliers.name',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['supplier_name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'total', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::PURCHASE;
    }

    protected function modelClass(): string
    {
        return Purchase::class;
    }

    protected function storeRequestClass(): ?string
    {
        return PurchaseRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return PurchaseRequest::class;
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'billableStatus' => $this->enumOptions(BillableStatus::class),
                'paymentMethods' => $this->enumOptions(PaymentMethod::class),
            ],
        ];
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'paymentMethods' => $this->enumOptions(PaymentMethod::class),
            ],
        ];
    }

    public function show(Request $request): Response|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        $purchase = $this->modelFromRoute($request)->load('items');

        if ($request->expectsJson()) {
            return response()->json($purchase);
        }

        $this->shareModuleRoutes();

        return Inertia::render($this->detailsComponent(), [
            $this->itemPropName() => $purchase,
            'id' => $purchase->getKey(),
            'routes' => $this->getModuleRoutes(),
            ...$this->moduleDetailsProps($purchase),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $purchase = DB::transaction(function () use ($request): Purchase {
            $data = $this->validatedRequestData($request, $this->storeRequestClass());
            $items = Arr::pull($data, 'items', []);

            /** @var Purchase $purchase */
            $purchase = $this->newModelQuery()->create($data);

            $this->billableItemService->syncPurchaseItems(
                $purchase,
                $items,
                (float) ($data['discount_value'] ?? 0),
            );

            return $purchase->load('items');
        });

        $this->stockRecalculationService->recalculateProducts(
            $purchase->items->pluck('product_id')->filter()->all(),
        );

        if ($request->expectsJson()) {
            return response()->json($purchase, 201);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' criado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        $purchase = DB::transaction(function () use ($request): Purchase {
            /** @var Purchase $purchase */
            $purchase = $this->modelFromRoute($request);
            $productIdsBeforeUpdate = $purchase->items()->pluck('product_id')->filter()->all();
            $data = $this->validatedRequestData($request, $this->updateRequestClass());
            $items = Arr::pull($data, 'items', []);

            $purchase->update($data);

            $this->billableItemService->syncPurchaseItems(
                $purchase,
                $items,
                (float) ($data['discount_value'] ?? 0),
            );

            $purchase = $purchase->refresh()->load('items');
            $purchase->setAttribute(
                'recalculation_product_ids',
                array_values(array_unique([
                    ...$productIdsBeforeUpdate,
                    ...$purchase->items->pluck('product_id')->filter()->all(),
                ])),
            );

            return $purchase;
        });

        $this->stockRecalculationService->recalculateProducts(
            $purchase->getAttribute('recalculation_product_ids') ?? [],
        );

        if ($request->expectsJson()) {
            return response()->json($purchase);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' atualizado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }
}

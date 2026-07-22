<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Enums\BillableStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\SaleRequest;
use App\Models\Sale;
use App\Services\BillableItemService;
use App\Services\BillingInvoiceService;
use App\Services\StockRecalculationService;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    use HasModule;

    public function __construct(
        private readonly BillableItemService $billableItemService,
        private readonly BillingInvoiceService $billingInvoiceService,
        private readonly StockRecalculationService $stockRecalculationService,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'total', 'status', 'client_name', 'payment_method', 'created_at'];

    protected array $joins = ['client'];

    /**
     * @var array<string, string>
     */
    protected array $fieldsMapping = [
        'id' => 'sales.id',
        'total' => 'sales.total',
        'status' => 'sales.status',
        'payment_method' => 'sales.payment_method',
        'created_at' => 'sales.created_at',
        'client_name' => 'clients.name',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['client_name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'total', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::SALE;
    }

    protected function modelClass(): string
    {
        return Sale::class;
    }

    protected function storeRequestClass(): ?string
    {
        return SaleRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return SaleRequest::class;
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

        $sale = $this->modelFromRoute($request)->load('items');

        if ($request->expectsJson()) {
            return response()->json($sale);
        }

        $this->shareModuleRoutes();

        return Inertia::render($this->detailsComponent(), [
            $this->itemPropName() => $sale,
            'id' => $sale->getKey(),
            'routes' => $this->getModuleRoutes(),
            ...$this->moduleDetailsProps($sale),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $sale = DB::transaction(function () use ($request): Sale {
            $data = $this->validatedRequestData($request, $this->storeRequestClass());
            $items = Arr::pull($data, 'items', []);
            $generateInvoices = (bool) Arr::pull($data, 'generate_invoices', true);

            /** @var Sale $sale */
            $sale = $this->newModelQuery()->create($data);

            $this->billableItemService->syncSaleItems(
                $sale,
                $items,
                (float) ($data['discount_value'] ?? 0),
            );

            $sale = $sale->refresh();

            if ($generateInvoices) {
                $invoices = $this->billingInvoiceService->generate($sale);

                $this->queueGatewayInvoiceSync($invoices);
            }

            return $sale->load('items');
        });

        $this->stockRecalculationService->recalculateProducts(
            $sale->items->pluck('product_id')->filter()->all(),
        );

        if ($request->expectsJson()) {
            return response()->json($sale, 201);
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

        /** @var Sale $sale */
        $sale = $this->modelFromRoute($request);

        if ($sale->status !== BillableStatus::OPEN->value) {
            return $this->blockedSaleUpdateResponse(
                $request,
                'Somente vendas pendentes podem ser atualizadas.',
            );
        }

        if ($sale->invoices()->whereHas('gatewayPayment')->exists()) {
            return $this->blockedSaleUpdateResponse(
                $request,
                'Vendas com faturas vinculadas a transações no gateway não podem ser atualizadas.',
            );
        }

        $sale = DB::transaction(function () use ($request, $sale): Sale {
            $productIdsBeforeUpdate = $sale->items()->pluck('product_id')->filter()->all();
            $data = $this->validatedRequestData($request, $this->updateRequestClass());
            $items = Arr::pull($data, 'items', []);
            $generateInvoices = (bool) Arr::pull($data, 'generate_invoices', true);

            $sale->invoices()->delete();

            $sale->update($data);

            $this->billableItemService->syncSaleItems(
                $sale,
                $items,
                (float) ($data['discount_value'] ?? 0),
            );

            $sale = $sale->refresh()->load('items');

            if ($generateInvoices) {
                $invoices = $this->billingInvoiceService->generate($sale);

                $this->queueGatewayInvoiceSync($invoices);
            }

            $sale->setAttribute(
                'recalculation_product_ids',
                array_values(array_unique([
                    ...$productIdsBeforeUpdate,
                    ...$sale->items->pluck('product_id')->filter()->all(),
                ])),
            );

            return $sale;
        });

        $this->stockRecalculationService->recalculateProducts(
            $sale->getAttribute('recalculation_product_ids') ?? [],
        );

        if ($request->expectsJson()) {
            return response()->json($sale);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' atualizado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
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

    private function blockedSaleUpdateResponse(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], 422);
        }

        Inertia::flash('dialog', [
            'type' => 'error',
            'title' => 'Não foi possível atualizar a venda',
            'message' => $message,
        ]);

        return back();
    }
}

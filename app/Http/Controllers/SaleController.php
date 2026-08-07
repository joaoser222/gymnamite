<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Exceptions\UpdateBillableBlockedException;
use App\Actions\Sales\CreateSaleAction;
use App\Actions\Sales\UpdateSaleAction;
use App\DTOs\Sales\CreateSaleDTO;
use App\DTOs\Sales\UpdateSaleDTO;
use App\Enums\BillableStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\SaleRequest;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends CrudModuleController
{
    public function __construct(
        private readonly CreateSaleAction $createSaleAction,
        private readonly UpdateSaleAction $updateSaleAction,
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

        /** @var Sale $sale */
        $sale = $this->createSaleAction->execute(CreateSaleDTO::fromValidatedData(
            $this->validatedRequestData($request, $this->storeRequestClass()),
        ));

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

        try {
            $sale = $this->updateSaleAction->execute(UpdateSaleDTO::fromValidatedData(
                $sale,
                $this->validatedRequestData($request, $this->updateRequestClass()),
            ));
        } catch (UpdateBillableBlockedException $exception) {
            return $this->blockedSaleUpdateResponse($request, $exception->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json($sale);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' atualizado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
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

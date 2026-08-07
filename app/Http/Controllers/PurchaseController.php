<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Exceptions\UpdateBillableBlockedException;
use App\Actions\Purchases\CreatePurchaseAction;
use App\Actions\Purchases\UpdatePurchaseAction;
use App\DTOs\Purchases\CreatePurchaseDTO;
use App\DTOs\Purchases\UpdatePurchaseDTO;
use App\Enums\BillableStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\PurchaseRequest;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends CrudModuleController
{
    public function __construct(
        private readonly CreatePurchaseAction $createPurchaseAction,
        private readonly UpdatePurchaseAction $updatePurchaseAction,
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

        $purchase = $this->modelFromRoute($request)->load('items', 'invoices');

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

        /** @var Purchase $purchase */
        $purchase = $this->createPurchaseAction->execute(CreatePurchaseDTO::fromValidatedData(
            $this->validatedRequestData($request, $this->storeRequestClass()),
        ));

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

        /** @var Purchase $purchase */
        $purchase = $this->modelFromRoute($request);

        try {
            $purchase = $this->updatePurchaseAction->execute(UpdatePurchaseDTO::fromValidatedData(
                $purchase,
                $this->validatedRequestData($request, $this->updateRequestClass()),
            ));
        } catch (UpdateBillableBlockedException $exception) {
            abort(403, $exception->getMessage());
        }

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

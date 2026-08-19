<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Payables\CreatePayableAction;
use App\Actions\Payables\UpdatePayableAction;
use App\DTOs\Payables\CreatePayableDTO;
use App\DTOs\Payables\UpdatePayableDTO;
use App\Enums\InvoiceStatus;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Models\Payable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayableController extends CrudModuleController
{
    public function __construct(
        private readonly CreatePayableAction $createPayable,
        private readonly UpdatePayableAction $updatePayable,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'due_date', 'payment_date', 'total', 'status', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['due_date', 'payment_date', 'status'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'due_date', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::PAYABLE;
    }

    protected function modelClass(): string
    {
        return Payable::class;
    }

    protected function newModelQuery(): Builder
    {
        return Payable::query()->where('operation_type', OperationType::PAYABLE->value);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createPayable->execute(
            CreatePayableDTO::from($request->validate([
                'supplier_id' => ['required', 'integer', 'min:1'],
                'due_date' => ['required', 'date'],
                'total' => ['required', 'numeric', 'min:0'],
                'payment_method' => ['required', 'string', 'in:pix,boleto,credit_card,cash'],
                'operation_type' => ['required', 'string', 'in:receivable,payable'],
                'annotations' => ['nullable', 'string', 'max:500'],
                'financial_account_id' => ['nullable', 'integer', 'min:1'],
                'financial_category_id' => ['nullable', 'integer', 'min:1'],
            ]))
        );

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json($result->data, 201);
        }

        return redirect()->route($this->routePrefix().'.index');
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        /** @var Payable $payable */
        $payable = $this->modelFromRoute($request);

        $result = $this->updatePayable->execute(
            UpdatePayableDTO::from([
                ...$request->validate([
                    'supplier_id' => ['nullable', 'integer', 'min:1'],
                    'due_date' => ['nullable', 'date'],
                    'total' => ['nullable', 'numeric', 'min:0'],
                    'payment_method' => ['nullable', 'string', 'in:pix,boleto,credit_card,cash'],
                    'operation_type' => ['nullable', 'string', 'in:receivable,payable'],
                    'annotations' => ['nullable', 'string', 'max:500'],
                    'financial_account_id' => ['nullable', 'integer', 'min:1'],
                    'financial_category_id' => ['nullable', 'integer', 'min:1'],
                ]),
                'id' => $payable->getKey(),
            ])
        );

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json($result->data);
        }

        return redirect()->route($this->routePrefix().'.index');
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'invoiceStatus' => $this->enumOptions(InvoiceStatus::class),
            ],
        ];
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'invoiceStatus' => $this->enumOptions(InvoiceStatus::class),
                'paymentMethods' => $this->enumOptions(PaymentMethod::class),
            ],
        ];
    }

    private function actionFailureResponse(Request $request, ?array $errors, ?string $message): RedirectResponse|JsonResponse
    {
        $message ??= 'Não foi possível concluir a operação.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => $errors,
            ], 422);
        }

        return back()->withErrors($errors ?? ['action' => $message])->withInput();
    }
}

<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Enums\InvoiceStatus;
use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Http\Requests\ReceivableSettlementRequest;
use App\Models\Movement;
use App\Models\Receivable;
use App\PaymentGateways\Contracts\PaymentGatewayAdapter;
use App\Services\GatewayInvoicingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ReceivableController extends CrudModuleController
{
    public function __construct(
        private readonly GatewayInvoicingService $invoicingService,
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
        return AccessModule::RECEIVABLE;
    }

    protected function modelClass(): string
    {
        return Receivable::class;
    }

    protected function newModelQuery(): Builder
    {
        return Receivable::query()->where('operation_type', OperationType::RECEIVABLE->value);
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

    /**
     * Retorna as rotas disponíveis para o frontend.
     */
    protected function getModuleRoutes(): array
    {
        $routes = parent::getModuleRoutes();
        $markPaidRoute = route('receivables.mark-paid', ['receivable' => '__id__']);

        return [
            ...$routes,
            'markPaid' => str_replace('__id__', ':id', $markPaidRoute),
            'requestGatewayInvoice' => str_replace(
                '__id__',
                ':id',
                route('receivables.request-gateway-invoice', ['receivable' => '__id__']),
            ),
        ];
    }

    /**
     * @param  class-string<FormRequest>|null  $formRequestClass
     * @return array<string, mixed>
     */
    protected function validatedRequestData(Request $request, ?string $formRequestClass): array
    {
        $data = parent::validatedRequestData($request, $formRequestClass);

        unset($data['payment_date'], $data['total']);

        if (array_key_exists('holder_id', $data)) {
            $data['holder_type'] = 'client';
        }

        return $data;
    }

    public function markPaid(ReceivableSettlementRequest $request, Receivable $receivable): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::MARK_PAID);

        abort_unless(
            $receivable->operation_type === OperationType::RECEIVABLE,
            404,
        );

        abort_if(
            $receivable->status === InvoiceStatus::PAID,
            422,
            'Este recebimento já foi baixado.',
        );

        $data = $request->validated();

        DB::transaction(function () use ($receivable, $data): void {
            $receivable->update([
                'payment_date' => $data['payment_date'],
                'paid_value' => $receivable->total,
                'status' => InvoiceStatus::PAID,
            ]);

            Movement::query()->create([
                'operation_type' => OperationType::RECEIVABLE,
                'movement_type' => $receivable->payment_method === PaymentMethod::CASH
                    ? MovementType::INTERNAL
                    : MovementType::EXTERNAL,
                'value' => $receivable->total,
                'invoice_id' => $receivable->id,
                'visibility' => 'visible',
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Recebimento baixado com sucesso.',
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Recebimento baixado com sucesso.',
        ]);

        return redirect()->route('receivables.index');
    }

    public function show(Request $request): Response|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        /** @var Receivable $receivable */
        $receivable = $this->invoicingService->eligibilityQuery($this->newModelQuery())
            ->whereKey($this->modelFromRoute($request)->getKey())
            ->with('gatewayPayment')
            ->firstOrFail();

        if ($request->expectsJson()) {
            return response()->json($receivable);
        }

        $this->shareModuleRoutes();

        return Inertia::render($this->detailsComponent(), [
            $this->itemPropName() => $receivable,
            'id' => $receivable->getKey(),
            'routes' => $this->getModuleRoutes(),
            'pixQrCode' => $this->pixQrCode($receivable),
            ...$this->moduleDetailsProps($receivable),
        ]);
    }

    public function requestGatewayInvoice(Request $request, Receivable $receivable): JsonResponse|RedirectResponse
    {
        $this->authorizeAccess(AccessAction::REQUEST_INVOICE);
        abort_unless($receivable->operation_type === OperationType::RECEIVABLE, 404);

        try {
            $gatewayInvoice = $this->invoicingService->request($receivable);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withErrors(['gateway_invoice' => $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json($gatewayInvoice, 201);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Nota fiscal solicitada com sucesso.',
        ]);

        return back();
    }

    private function pixQrCode(Receivable $receivable): ?array
    {
        if ($receivable->payment_method !== PaymentMethod::PIX || $receivable->gatewayPayment === null) {
            return null;
        }

        try {
            return app(PaymentGatewayAdapter::class)->getPixQrCode($receivable->gatewayPayment);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}

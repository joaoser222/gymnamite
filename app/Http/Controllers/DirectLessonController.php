<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Enums\BillableStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\DirectLessonRequest;
use App\Models\DirectLesson;
use App\Services\BillingInvoiceService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DirectLessonController extends CrudModuleController
{
    public function __construct(
        private readonly BillingInvoiceService $billingInvoiceService,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'lesson_date', 'price', 'status', 'payment_method', 'created_at'];

    protected array $joins = ['client', 'trainer'];

    /**
     * @var array<string, string>
     */
    protected array $fieldsMapping = [
        'id' => 'direct_lessons.id',
        'lesson_date' => 'direct_lessons.lesson_date',
        'price' => 'direct_lessons.price',
        'status' => 'direct_lessons.status',
        'payment_method' => 'direct_lessons.payment_method',
        'created_at' => 'direct_lessons.created_at',
        'client_name' => 'clients.name',
        'trainer_name' => 'trainers.name',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['client_name', 'trainer_name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'lesson_date', 'payment_method', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::DIRECT_LESSON;
    }

    protected function modelClass(): string
    {
        return DirectLesson::class;
    }

    protected function storeRequestClass(): ?string
    {
        return DirectLessonRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return DirectLessonRequest::class;
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

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $directLesson = DB::transaction(function () use ($request): DirectLesson {
            $data = $this->validatedRequestData($request, $this->storeRequestClass());
            $generateInvoices = (bool) Arr::pull($data, 'generate_invoices', true);

            /** @var DirectLesson $directLesson */
            $directLesson = $this->newModelQuery()->create($data);

            if ($generateInvoices) {
                $invoices = $this->billingInvoiceService->generate($directLesson);

                $this->queueGatewayInvoiceSync($invoices);
            }

            return $directLesson;
        });

        if ($request->expectsJson()) {
            return response()->json($directLesson, 201);
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

        /** @var DirectLesson $directLesson */
        $directLesson = $this->modelFromRoute($request);

        if ($directLesson->invoices()->whereHas('gatewayPayment')->exists()) {
            return $this->blockedDirectLessonUpdateResponse(
                $request,
                'Aulas avulsas com faturas vinculadas a transações no gateway não podem ser atualizadas.',
            );
        }

        $directLesson = DB::transaction(function () use ($request, $directLesson): DirectLesson {
            $data = $this->validatedRequestData($request, $this->updateRequestClass());
            $generateInvoices = (bool) Arr::pull($data, 'generate_invoices', true);

            $directLesson->invoices()->delete();
            $directLesson->update($data);

            if ($generateInvoices) {
                $invoices = $this->billingInvoiceService->generate($directLesson->refresh());

                $this->queueGatewayInvoiceSync($invoices);
            }

            return $directLesson->refresh();
        });

        if ($request->expectsJson()) {
            return response()->json($directLesson);
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

    private function blockedDirectLessonUpdateResponse(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], 422);
        }

        Inertia::flash('dialog', [
            'type' => 'error',
            'title' => 'Não foi possível atualizar a aula avulsa',
            'message' => $message,
        ]);

        return back();
    }
}

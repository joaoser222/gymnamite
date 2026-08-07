<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\DirectLessons\CreateDirectLessonAction;
use App\Actions\DirectLessons\UpdateDirectLessonAction;
use App\Actions\Exceptions\UpdateBillableBlockedException;
use App\DTOs\DirectLessons\CreateDirectLessonDTO;
use App\DTOs\DirectLessons\UpdateDirectLessonDTO;
use App\Enums\BillableStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\DirectLessonRequest;
use App\Models\DirectLesson;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DirectLessonController extends CrudModuleController
{
    public function __construct(
        private readonly CreateDirectLessonAction $createDirectLessonAction,
        private readonly UpdateDirectLessonAction $updateDirectLessonAction,
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

        /** @var DirectLesson $directLesson */
        $directLesson = $this->createDirectLessonAction->execute(CreateDirectLessonDTO::fromValidatedData(
            $this->validatedRequestData($request, $this->storeRequestClass()),
        ));

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

        try {
            $directLesson = $this->updateDirectLessonAction->execute(UpdateDirectLessonDTO::fromValidatedData(
                $directLesson,
                $this->validatedRequestData($request, $this->updateRequestClass()),
            ));
        } catch (UpdateBillableBlockedException $exception) {
            return $this->blockedDirectLessonUpdateResponse($request, $exception->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json($directLesson);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' atualizado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
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

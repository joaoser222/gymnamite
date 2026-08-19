<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\CostCenters\CreateCostCenterAction;
use App\Actions\CostCenters\UpdateCostCenterAction;
use App\DTOs\CostCenters\CreateCostCenterDTO;
use App\DTOs\CostCenters\UpdateCostCenterDTO;
use App\Enums\OperationType;
use App\Models\CostCenter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CostCenterController extends CrudModuleController
{
    public function __construct(
        private readonly CreateCostCenterAction $createCostCenter,
        private readonly UpdateCostCenterAction $updateCostCenter,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'color', 'operation_type', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::COST_CENTER;
    }

    protected function modelClass(): string
    {
        return CostCenter::class;
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createCostCenter->execute(
            CreateCostCenterDTO::from($request->validate([
                'name' => ['required', 'string', 'max:255'],
                'color' => ['nullable', 'string', 'max:7'],
                'operation_type' => ['required', 'string', 'in:receivable,payable'],
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

        /** @var CostCenter $costCenter */
        $costCenter = $this->modelFromRoute($request);

        $result = $this->updateCostCenter->execute(
            UpdateCostCenterDTO::from([
                ...$request->validate([
                    'name' => ['nullable', 'string', 'max:255'],
                    'color' => ['nullable', 'string', 'max:7'],
                    'operation_type' => ['nullable', 'string', 'in:receivable,payable'],
                ]),
                'id' => $costCenter->getKey(),
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
                'operationTypes' => $this->enumOptions(OperationType::class),
            ],
        ];
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'operationTypes' => $this->enumOptions(OperationType::class),
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

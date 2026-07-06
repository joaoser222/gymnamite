<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Http\Requests\PlanRequest;
use App\Models\Modality;
use App\Models\Plan;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'description', 'created_at'];

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
        return AccessModule::PLAN;
    }

    protected function modelClass(): string
    {
        return Plan::class;
    }

    protected function storeRequestClass(): ?string
    {
        return PlanRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return PlanRequest::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'modalities' => Modality::query()
                    ->select(['id', 'name'])
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Modality $modality): array => [
                        'value' => $modality->id,
                        'label' => $modality->name,
                    ])
                    ->all(),
            ],
        ];
    }

    public function show(Request $request): Response|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        /** @var Plan $plan */
        $plan = $this->modelFromRoute($request)->load(['tiers', 'modalities']);
        $plan->setAttribute('plan_modalities', $plan->modalities->pluck('modality_id')->all());

        if ($request->expectsJson()) {
            return response()->json($plan);
        }

        $this->shareModuleRoutes();

        return Inertia::render($this->detailsComponent(), [
            $this->itemPropName() => $plan,
            'id' => $plan->getKey(),
            'routes' => $this->getModuleRoutes(),
            ...$this->moduleDetailsProps($plan),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $plan = DB::transaction(function () use ($request): Plan {
            $data = $this->validatedRequestData($request, $this->storeRequestClass());

            return $this->persistPlan(new Plan, $data);
        });

        if ($request->expectsJson()) {
            return response()->json($plan, 201);
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

        $plan = DB::transaction(function () use ($request): Plan {
            /** @var Plan $plan */
            $plan = $this->modelFromRoute($request);
            $data = $this->validatedRequestData($request, $this->updateRequestClass());

            return $this->persistPlan($plan, $data);
        });

        if ($request->expectsJson()) {
            return response()->json($plan);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' atualizado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persistPlan(Plan $plan, array $data): Plan
    {
        $tiers = Arr::pull($data, 'tiers', []);
        $planModalities = Arr::pull($data, 'plan_modalities', []);

        if (! $plan->exists) {
            $plan = $this->newModelQuery()->create($data);
        } else {
            $plan->update($data);
        }

        $plan->tiers()->delete();
        $plan->tiers()->createMany($tiers);

        $plan->modalities()->delete();
        $plan->modalities()->createMany(array_map(
            fn (int $modalityId): array => ['modality_id' => $modalityId],
            $planModalities,
        ));

        return $plan->refresh()->load(['tiers', 'modalities']);
    }
}

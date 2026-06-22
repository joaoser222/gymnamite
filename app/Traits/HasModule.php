<?php

namespace App\Traits;

use App\AccessControl\AccessAction;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

trait HasModule
{
    use AuthorizesAccessControl;

    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * Retorna as rotas disponíveis para o frontend
     */
    protected function getModuleRoutes(): array
    {
        $prefix = $this->routePrefix();
        $parameterName = $this->routeParameterName();
        $showRoute = route("{$prefix}.show", [$parameterName => '__id__']);
        $updateRoute = route("{$prefix}.update", [$parameterName => '__id__']);

        return [
            'index' => route("{$prefix}.index"),
            'create' => route("{$prefix}.create"),
            'new' => route("{$prefix}.create"),
            'show' => str_replace('__id__', ':id', $showRoute),
            'store' => route("{$prefix}.store"),
            'update' => str_replace('__id__', ':id', $updateRoute),
            'destroy' => route("{$prefix}.destroy"),
            'changeVisibility' => route("{$prefix}.change-visibility"),
        ];
    }

    /**
     * Compartilha as rotas com o Inertia
     */
    protected function shareModuleRoutes(): void
    {
        Inertia::share('moduleRoutes', function () {
            return $this->getModuleRoutes();
        });
    }

    public function index(Request $request): Response|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        $this->shareModuleRoutes();

        $filters = [
            'page' => (int) $request->input('page', 1),
            'search' => (string) $request->input('search', ''),
            'searchField' => (string) $request->input('searchField', $request->input('search_field', $this->defaultSearchField())),
            'visibility' => (string) $request->input('visibility', 'visible'),
            'sortBy' => (string) $request->input('sortBy', $request->input('sort_by', 'id')),
        ];

        $searchField = in_array($filters['searchField'], $this->searchableFields(), true)
            ? $filters['searchField']
            : $this->defaultSearchField();

        $sortBy = in_array($filters['sortBy'], $this->sortableFields(), true)
            ? $filters['sortBy']
            : 'id';

        $records = $this->newModelQuery()
            ->when(
                ! empty($this->fields()),
                fn (Builder $query) => $query->select($this->fields()),
            )
            ->when(
                ! empty($this->joins()),
                fn (Builder $query) => $this->applyJoins($query),
            )
            ->where('visibility', $filters['visibility'])
            ->when(
                $filters['search'] !== '' && $searchField !== null,
                fn (Builder $query) => $query->where($searchField, 'like', '%'.$filters['search'].'%'),
            )
            ->orderBy($sortBy, 'desc')
            ->paginate(15)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'results' => $records->items(),
                'count' => $records->total(),
                'per_page' => $records->perPage(),
                'num_pages' => $records->lastPage(),
                'page' => $records->currentPage(),
            ]);
        }

        return Inertia::render($this->indexComponent(), [
            $this->collectionPropName() => $records,
            'filters' => array_merge($filters, [
                'searchField' => $searchField,
                'sortBy' => $sortBy,
            ]),
            'id' => $this->pageItemId($request),
            'routes' => $this->getModuleRoutes(),
            ...$this->moduleIndexProps($request),
        ]);
    }

    public function create(): Response
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $this->shareModuleRoutes();

        return Inertia::render($this->detailsComponent(), [
            $this->itemPropName() => null,
            'id' => 'new',
            'routes' => $this->getModuleRoutes(),
            ...$this->moduleDetailsProps(),
        ]);
    }

    public function show(Request $request): Response|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        $model = $this->modelFromRoute($request);

        if ($request->expectsJson()) {
            return response()->json($model);
        }

        $this->shareModuleRoutes();

        return Inertia::render($this->detailsComponent(), [
            $this->itemPropName() => $model,
            'id' => $model->getKey(),
            'routes' => $this->getModuleRoutes(),
            ...$this->moduleDetailsProps($model),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $model = $this->newModelQuery()->create(
            $this->validatedRequestData($request, $this->storeRequestClass())
        );

        if ($request->expectsJson()) {
            return response()->json($model, 201);
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

        $model = $this->modelFromRoute($request);

        $model->update(
            $this->validatedRequestData($request, $this->updateRequestClass())
        );

        if ($request->expectsJson()) {
            return response()->json($model->refresh());
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' atualizado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::DELETE);

        // Tenta obter os IDs de diferentes fontes
        $ids = $this->extractIdsFromRequest($request);

        if (empty($ids)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Nenhum ID fornecido para deleção.',
                ], 422);
            }

            return back()->with('error', 'Nenhum item selecionado para deletar.');
        }

        // Valida se os IDs existem
        $validIds = $this->newModelQuery()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->toArray();

        if (empty($validIds)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Nenhum dos IDs fornecidos é válido.',
                ], 404);
            }

            return back()->with('error', 'Nenhum item válido encontrado para deletar.');
        }

        // Executa a deleção
        $deletedCount = $this->newModelQuery()
            ->whereIn('id', $validIds)
            ->delete();

        // Prepara a mensagem
        $moduleLabel = $this->accessModule()->label();
        $message = $deletedCount > 1
            ? "{$deletedCount} {$moduleLabel} removidos com sucesso."
            : "{$moduleLabel} removido com sucesso.";

        if ($request->expectsJson()) {
            return response()->json([
                'deleted' => $deletedCount,
                'items' => $validIds,
                'message' => __($message),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($message),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    /**
     * Extrai IDs da requisição de diferentes fontes
     *
     * @return array<int, int>
     */
    protected function extractIdsFromRequest(Request $request): array
    {
        $ids = [];

        if (empty($ids) && $request->has('items')) {
            $ids = $request->input('items');
            if (is_string($ids)) {
                $ids = explode(',', $ids);
            }
        }

        // Garante que é um array de inteiros
        return array_filter(array_map('intval', (array) $ids));
    }

    /**
     * Altera a visibilidade de múltiplos registros
     */
    public function changeVisibility(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VISIBILITY);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['integer', 'exists:'.$this->modelClass().',id'],
            'visibility' => ['required', 'string', 'in:visible,hidden,archived'],
        ]);

        $count = $this->newModelQuery()
            ->whereKey($data['items'])
            ->update(['visibility' => $data['visibility']]);

        $message = $count > 1
            ? "Visibilidade de {$count} {$this->accessModule()->label()} atualizada com sucesso."
            : "Visibilidade de {$this->accessModule()->label()} atualizada com sucesso.";

        if ($request->expectsJson()) {
            return response()->json([
                'updated' => $count,
                'message' => __($message),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($message),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    protected function routePrefix(): string
    {
        return $this->accessModule()->value;
    }

    protected function routeParameterName(): string
    {
        return Str::singular($this->routePrefix());
    }

    protected function indexComponent(): string
    {
        return Str::of($this->routePrefix())
            ->singular()
            ->replace('_', '-')
            ->append('/Index')
            ->toString();
    }

    protected function detailsComponent(): string
    {
        return Str::of($this->routePrefix())
            ->singular()
            ->replace('_', '-')
            ->append('/Details')
            ->toString();
    }

    protected function collectionPropName(): string
    {
        return $this->routePrefix();
    }

    protected function itemPropName(): string
    {
        return Str::singular($this->routePrefix());
    }

    /**
     * @return class-string<FormRequest>|null
     */
    protected function storeRequestClass(): ?string
    {
        return null;
    }

    /**
     * @return class-string<FormRequest>|null
     */
    protected function updateRequestClass(): ?string
    {
        return null;
    }

    protected function defaultSearchField(): ?string
    {
        return $this->searchableFields()[0] ?? null;
    }

    /**
     * Campos que serão retornados para o frontend.
     * Se vazio, retorna todos os campos.
     *
     * @return array<int, string>
     */
    protected function fields(): array
    {
        return property_exists($this, 'fields')
            ? $this->fields
            : [];
    }

    /**
     * Relações Eloquent que devem ser joined na listagem.
     * Permite usar campos relacionados em searchableFields e sortableFields.
     *
     * @return array<int, string>
     */
    protected function joins(): array
    {
        return property_exists($this, 'joins')
            ? $this->joins
            : [];
    }

    /**
     * Aplica os joins definidos na query.
     */
    protected function applyJoins(Builder $query): void
    {
        $model = new $this->modelClass;

        foreach ($this->joins() as $relation) {
            $relationObject = $model->$relation();

            $query->join(
                $relationObject->getRelated()->getTable(),
                $relationObject->getQualifiedForeignKeyName(),
                '=',
                $relationObject->getQualifiedOwnerKeyName(),
            );
        }
    }

    /**
     * @return array<int, string>
     */
    protected function searchableFields(): array
    {
        return property_exists($this, 'searchableFields')
            ? $this->searchableFields
            : [];
    }

    /**
     * @return array<int, string>
     */
    protected function sortableFields(): array
    {
        return property_exists($this, 'sortableFields')
            ? $this->sortableFields
            : ['id', 'created_at', 'updated_at'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function moduleIndexProps(Request $request): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [];
    }

    /**
     * @param  class-string<BackedEnum>  $enumClass
     * @return array<int, array{value: string, label: string}>
     */
    protected function enumOptions(string $enumClass): array
    {
        return array_map(
            fn (BackedEnum $case): array => [
                'value' => (string) $case->value,
                'label' => method_exists($case, 'label') ? $case->label() : (string) $case->value,
            ],
            $enumClass::cases(),
        );
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<int, array{value: string, label: string}>
     */
    protected function modelOptions(string $modelClass): array
    {
        return $modelClass::query()
            ->select(['code', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Model $model): array => [
                'value' => (string) $model->getAttribute('code'),
                'label' => (string) $model->getAttribute('name'),
            ])
            ->all();
    }

    protected function modelFromRoute(Request $request): Model
    {
        $routeValue = $request->route($this->routeParameterName());

        if ($routeValue instanceof Model) {
            return $routeValue;
        }

        $model = $this->newModelQuery()->findOrFail($routeValue);

        $request->route()?->setParameter($this->routeParameterName(), $model);

        return $model;
    }

    protected function pageItemId(Request $request): string|int|null
    {
        if ($request->routeIs($this->routePrefix().'.create')) {
            return 'new';
        }

        $routeValue = $request->route($this->routeParameterName());

        if ($routeValue instanceof Model) {
            return $routeValue->getKey();
        }

        return $routeValue;
    }

    protected function newModelQuery(): Builder
    {
        return $this->modelClass()::query();
    }

    /**
     * @param  class-string<FormRequest>|null  $formRequestClass
     * @return array<string, mixed>
     */
    protected function validatedRequestData(Request $request, ?string $formRequestClass): array
    {
        if ($formRequestClass === null) {
            return $request->all();
        }

        $formRequest = $formRequestClass::createFrom($request);
        $formRequest->setContainer(app());
        $formRequest->setRedirector(app('redirect'));
        $formRequest->setUserResolver($request->getUserResolver());
        $formRequest->setRouteResolver($request->getRouteResolver());
        $formRequest->validateResolved();

        return $formRequest->validated();
    }
}

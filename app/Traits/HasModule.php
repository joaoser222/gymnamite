<?php

namespace App\Traits;

use App\AccessControl\AccessAction;
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

    public function index(Request $request): Response|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

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
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        return response()->json($this->modelFromRoute($request));
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

        $this->modelFromRoute($request)->delete();

        if ($request->expectsJson()) {
            return response()->json(null, 204);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' removido com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorizeAccess(AccessAction::DELETE);

        $ids = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ])['ids'];

        $this->newModelQuery()->whereKey($ids)->delete();

        return response()->json(null, 204);
    }

    public function bulkChangeVisibility(Request $request): JsonResponse
    {
        $this->authorizeAccess(AccessAction::VISIBILITY);

        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'visibility' => ['required', 'string'],
        ]);

        $this->newModelQuery()
            ->whereKey($data['ids'])
            ->update(['visibility' => $data['visibility']]);

        return response()->json(null, 204);
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

    protected function collectionPropName(): string
    {
        return $this->routePrefix();
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

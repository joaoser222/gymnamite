<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

abstract class ReadOnlyModuleController extends AbstractModuleController
{
    public function index(Request $request): Response|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        $filters = [
            'page' => (int) $request->input('page', 1),
            'search' => (string) $request->input('search', ''),
            'searchField' => (string) $request->input('searchField', $request->input('search_field', $this->defaultSearchField())),
            'sortBy' => (string) $request->input('sortBy', $request->input('sort_by', 'id')),
            'sortDirection' => (string) $request->input('sortDirection', $request->input('sort_direction', 'desc')),
        ];

        $searchField = in_array($filters['searchField'], $this->searchableFields(), true)
            ? $filters['searchField']
            : $this->defaultSearchField();

        $sortBy = in_array($filters['sortBy'], $this->sortableFields(), true)
            ? $filters['sortBy']
            : 'id';

        $sortDirection = strtolower($filters['sortDirection']) === 'asc' ? 'asc' : 'desc';

        $records = $this->newModelQuery()
            ->when(
                ! empty($this->fields()),
                fn (Builder $query) => $query->addSelect($this->resolveSelectColumns()),
            )
            ->when(
                $filters['search'] !== '' && $searchField !== null,
                fn (Builder $query) => $query->where($this->resolveSearchColumn($searchField), 'like', '%'.$filters['search'].'%'),
            )
            ->orderBy($this->resolveSearchColumn($sortBy), $sortDirection)
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
            'filters' => [
                ...$filters,
                'searchField' => $searchField,
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
            ],
            'routes' => $this->getModuleRoutes(),
            ...$this->moduleIndexProps($request),
        ]);
    }

    public function show(Request $request): Response|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        $model = $this->modelFromRoute($request);

        if ($request->expectsJson()) {
            return response()->json($model);
        }

        return Inertia::render($this->detailsComponent(), [
            $this->itemPropName() => $model,
            'routes' => $this->getModuleRoutes(),
            ...$this->moduleIndexProps($request),
        ]);
    }

    protected function indexComponent(): string
    {
        return Str::of($this->accessModule()->value)
            ->append('/Index')
            ->toString();
    }

    protected function detailsComponent(): string
    {
        return Str::of($this->accessModule()->value)
            ->append('/Details')
            ->toString();
    }

    protected function collectionPropName(): string
    {
        return Str::camel($this->accessModule()->value);
    }

    protected function itemPropName(): string
    {
        return Str::camel(Str::singular($this->accessModule()->value));
    }

    /**
     * @return array<string, string>
     */
    protected function getModuleRoutes(): array
    {
        $showRoute = route($this->routePrefix().'.show', [$this->routeParameterName() => '__id__']);

        return [
            'index' => route($this->routePrefix().'.index'),
            'show' => str_replace('__id__', ':id', $showRoute),
        ];
    }
}

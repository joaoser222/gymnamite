<?php

namespace App\Traits;

use App\AccessControl\AccessAction;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

trait HasReadOnlyModule
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
        ]);
    }

    protected function routePrefix(): string
    {
        return str_replace('_', '-', $this->accessModule()->value);
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

    protected function routeParameterName(): string
    {
        return Str::of($this->routePrefix())->replace('-', '_')->singular()->toString();
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

    protected function defaultSearchField(): ?string
    {
        return $this->searchableFields()[0] ?? null;
    }

    /**
     * @return array<int, string>
     */
    protected function fields(): array
    {
        return property_exists($this, 'fields')
            ? $this->fields
            : [];
    }

    /**
     * @return array<string, string>
     */
    protected function fieldsMapping(): array
    {
        return property_exists($this, 'fieldsMapping')
            ? $this->fieldsMapping
            : [];
    }

    /**
     * @return array<int, string|Expression>
     */
    protected function resolveSelectColumns(): array
    {
        $mapping = $this->fieldsMapping();

        return array_map(
            fn (string $field) => isset($mapping[$field])
                ? \DB::raw("{$mapping[$field]} AS `{$field}`")
                : $field,
            $this->fields(),
        );
    }

    protected function resolveSearchColumn(string $field): string
    {
        $mapping = $this->fieldsMapping();

        return $mapping[$field] ?? $field;
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

    protected function newModelQuery(): Builder
    {
        return $this->modelClass()::query();
    }

    protected function modelFromRoute(Request $request): Model
    {
        $routeValue = $request->route($this->routeParameterName());

        if ($routeValue instanceof Model) {
            return $routeValue;
        }

        return $this->newModelQuery()->findOrFail($routeValue);
    }

    /**
     * @param  class-string<BackedEnum>  $enumClass
     * @return array<int, array{value: string, label: string, color: string}>
     */
    protected function enumOptions(string $enumClass): array
    {
        return array_map(
            fn (BackedEnum $case): array => [
                'value' => (string) $case->value,
                'label' => method_exists($case, 'label') ? $case->label() : (string) $case->value,
                'color' => method_exists($case, 'color') ? $case->color() : 'secondary',
            ],
            $enumClass::cases(),
        );
    }
}

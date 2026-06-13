<?php

namespace App\Traits;

use App\AccessControl\AccessAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
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

    public function index(Request $request): Response
    {
        $this->authorizeAccess(AccessAction::VIEW);

        $filters = [
            'page' => (int) $request->input('page', 1),
            'search' => (string) $request->input('search', ''),
            'searchField' => (string) $request->input('searchField', $this->defaultSearchField()),
            'visibility' => (string) $request->input('visibility', 'visible'),
            'sortBy' => (string) $request->input('sortBy', 'id'),
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

        return Inertia::render($this->indexComponent(), [
            $this->collectionPropName() => $records,
            'filters' => array_merge($filters, [
                'searchField' => $searchField,
                'sortBy' => $sortBy,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $this->newModelQuery()->create(
            $this->validatedRequestData($request, $this->storeRequestClass())
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' criado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        $model = $this->modelFromRoute($request);

        $model->update(
            $this->validatedRequestData($request, $this->updateRequestClass())
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' atualizado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->authorizeAccess(AccessAction::DELETE);

        $this->modelFromRoute($request)->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' removido com sucesso.'),
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
        return $this->routePrefix().'/Index';
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

<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

abstract class CrudModuleController extends AbstractModuleController
{
    /**
     * @return array<string, string>
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
                fn (Builder $query) => $query->addSelect($this->resolveSelectColumns()),
            )
            ->when(
                ! empty($this->joins()),
                fn (Builder $query) => $this->applyJoins($query),
            )
            ->where((new ($this->modelClass()))->getTable().'.visibility', $filters['visibility'])
            ->when(
                $filters['search'] !== '' && $searchField !== null,
                fn (Builder $query) => $query->where($this->resolveSearchColumn($searchField), 'like', '%'.$filters['search'].'%'),
            )
            ->orderBy($this->resolveSearchColumn($sortBy), 'desc')
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

        $ids = $this->extractIdsFromRequest($request);

        if (empty($ids)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Nenhum ID fornecido para deleção.',
                ], 422);
            }

            return back()->with('error', 'Nenhum item selecionado para deletar.');
        }

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

        $deletedCount = $this->newModelQuery()
            ->whereIn('id', $validIds)
            ->delete();

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

        return array_filter(array_map('intval', (array) $ids));
    }

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
}

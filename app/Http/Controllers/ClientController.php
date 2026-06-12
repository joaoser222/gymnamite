<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const SEARCHABLE_FIELDS = ['name', 'email', 'document', 'phone'];

    /**
     * @var array<int, string>
     */
    private const SORTABLE_FIELDS = ['id', 'name', 'created_at', 'updated_at'];

    public function index(Request $request): Response
    {
        $filters = [
            'page' => (int) $request->input('page', 1),
            'search' => (string) $request->input('search', ''),
            'searchField' => (string) $request->input('searchField', 'name'),
            'visibility' => (string) $request->input('visibility', 'visible'),
            'sortBy' => (string) $request->input('sortBy', 'id'),
        ];

        $searchField = in_array($filters['searchField'], self::SEARCHABLE_FIELDS, true)
            ? $filters['searchField']
            : 'name';

        $sortBy = in_array($filters['sortBy'], self::SORTABLE_FIELDS, true)
            ? $filters['sortBy']
            : 'id';

        $clients = Client::query()
            ->where('visibility', $filters['visibility'])
            ->when(
                $filters['search'] !== '',
                fn ($query) => $query->where($searchField, 'like', '%'.$filters['search'].'%'),
            )
            ->orderBy($sortBy, 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('clients/Index', [
            'clients' => $clients,
            'filters' => array_merge($filters, [
                'searchField' => $searchField,
                'sortBy' => $sortBy,
            ]),
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        Client::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Cliente criado com sucesso.'),
        ]);

        return redirect()->route('clients.index');
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Cliente atualizado com sucesso.'),
        ]);

        return redirect()->route('clients.index');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Cliente removido com sucesso.'),
        ]);

        return redirect()->route('clients.index');
    }
}

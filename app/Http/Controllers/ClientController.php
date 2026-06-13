<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Traits\HasModule;

class ClientController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name', 'email', 'document', 'phone'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'created_at', 'updated_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::CLIENT;
    }

    protected function modelClass(): string
    {
        return Client::class;
    }

    protected function storeRequestClass(): ?string
    {
        return StoreClientRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return UpdateClientRequest::class;
    }
}

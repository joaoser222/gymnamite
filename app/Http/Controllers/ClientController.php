<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\ClientStatus;
use App\Enums\GenderType;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Uf;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'document', 'status', 'phone', 'created_at', 'updated_at'];

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

    /**
     * @return array<string, mixed>
     */
    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'genderTypes' => $this->enumOptions(GenderType::class),
                'ufs' => $this->modelOptions(Uf::class)
            ],
            
        ];
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'clientStatus' => $this->enumOptions(ClientStatus::class)
            ],
            
        ];
    }
}

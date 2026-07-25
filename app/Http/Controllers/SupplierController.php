<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Models\Supplier;
use App\Models\Uf;
use Illuminate\Database\Eloquent\Model;

class SupplierController extends CrudModuleController
{
    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'email', 'document', 'phone', 'created_at', 'updated_at'];

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
        return AccessModule::SUPPLIER;
    }

    protected function modelClass(): string
    {
        return Supplier::class;
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'ufs' => $this->modelOptions(Uf::class),
            ],
        ];
    }
}

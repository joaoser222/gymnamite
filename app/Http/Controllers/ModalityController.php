<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Models\Modality;
use App\Traits\HasModule;

class ModalityController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::MODALITY;
    }

    protected function modelClass(): string
    {
        return Modality::class;
    }
}

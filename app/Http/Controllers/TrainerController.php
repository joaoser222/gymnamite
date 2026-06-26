<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\GenderType;
use App\Models\Trainer;
use App\Models\Uf;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Model;

class TrainerController extends Controller
{
    use HasModule;

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
        return AccessModule::TRAINER;
    }

    protected function modelClass(): string
    {
        return Trainer::class;
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'genderTypes' => $this->enumOptions(GenderType::class),
                'ufs' => $this->modelOptions(Uf::class),
            ],
        ];
    }
}

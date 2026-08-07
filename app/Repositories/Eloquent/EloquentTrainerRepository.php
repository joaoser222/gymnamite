<?php

namespace App\Repositories\Eloquent;

use App\Models\Trainer;
use App\Repositories\Contracts\TrainerRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class EloquentTrainerRepository extends BaseEloquentRepository implements TrainerRepositoryInterface
{
    protected function modelClass(): string
    {
        return Trainer::class;
    }

    protected array $with = [];

    protected array $searchableFields = ['name', 'document'];

    protected array $filterableFields = ['visibility'];

    protected string $defaultSort = 'name';

    protected string $defaultSortDirection = 'asc';

    public function findByDocument(string $document): ?Model
    {
        return $this->newQuery()->where('document', $document)->first();
    }
}

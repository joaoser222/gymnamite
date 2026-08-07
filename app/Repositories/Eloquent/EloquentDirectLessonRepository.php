<?php

namespace App\Repositories\Eloquent;

use App\Models\DirectLesson;
use App\Repositories\Contracts\DirectLessonRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentDirectLessonRepository extends BaseEloquentRepository implements DirectLessonRepositoryInterface
{
    protected function modelClass(): string
    {
        return DirectLesson::class;
    }

    protected array $with = ['client', 'trainer', 'modality'];

    protected array $searchableFields = ['status'];

    protected array $filterableFields = ['client_id', 'trainer_id', 'modality_id', 'status', 'visibility'];

    protected string $defaultSort = 'created_at';

    protected string $defaultSortDirection = 'desc';

    public function findWithRelations(int $id): ?Model
    {
        return $this->newQuery()->with($this->with)->find($id);
    }

    public function findByClient(int $clientId): Collection
    {
        return $this->newQuery()
            ->where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findPendingInvoices(): Collection
    {
        return $this->newQuery()
            ->whereHas('invoices', function ($query) {
                $query->where('status', 'pending');
            })
            ->get();
    }
}

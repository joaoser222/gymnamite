<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\BillableStatus;
use App\Models\Client;
use App\Models\DirectLesson;
use App\Models\Trainer;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DirectLessonController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'lesson_date', 'price', 'status', 'created_at'];

    protected array $joins = ['client', 'trainer'];

    /**
     * @var array<string, string>
     */
    protected array $fieldsMapping = [
        'id' => 'direct_lessons.id',
        'lesson_date' => 'direct_lessons.lesson_date',
        'price' => 'direct_lessons.price',
        'status' => 'direct_lessons.status',
        'created_at' => 'direct_lessons.created_at',
        'client_name' => 'clients.name',
        'trainer_name' => 'trainers.name',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['client_name', 'trainer_name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'lesson_date', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::DIRECT_LESSON;
    }

    protected function modelClass(): string
    {
        return DirectLesson::class;
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'billableStatus' => $this->enumOptions(BillableStatus::class),
            ],
        ];
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'clients' => $this->modelOptions(Client::class),
                'trainers' => $this->modelOptions(Trainer::class),
            ],
        ];
    }
}

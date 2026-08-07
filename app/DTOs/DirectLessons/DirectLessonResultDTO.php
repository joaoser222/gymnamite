<?php

namespace App\DTOs\DirectLessons;

use App\Models\DirectLesson;
use Spatie\LaravelData\Data;

class DirectLessonResultDTO extends Data
{
    public function __construct(
        public int $id,
        public float $price,
        public string $status,
        public string $payment_method,
        public string $lesson_date,
        public string $created_at,
        public int $client_id,
        public string $client_name,
        public int $trainer_id,
        public string $trainer_name,
        public int $modality_id,
        public string $modality_name,
    ) {}

    public static function fromModel(DirectLesson $lesson): static
    {
        return new static(
            id: $lesson->id,
            price: $lesson->price,
            status: $lesson->status->value,
            payment_method: $lesson->payment_method->value,
            lesson_date: $lesson->lesson_date?->format('Y-m-d') ?? '',
            created_at: $lesson->created_at?->toISOString() ?? '',
            client_id: $lesson->client_id,
            client_name: $lesson->client?->name ?? '',
            trainer_id: $lesson->trainer_id,
            trainer_name: $lesson->trainer?->name ?? '',
            modality_id: $lesson->modality_id,
            modality_name: $lesson->modality?->name ?? '',
        );
    }
}

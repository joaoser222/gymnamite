<?php

namespace App\DTOs\Trainer;

use App\Models\Trainer;
use Spatie\LaravelData\Data;

class TrainerResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $email,
        public string $document,
        public ?string $birth_date,
        public ?string $phone,
        public ?string $gender,
        public string $created_at,
    ) {}

    public static function fromModel(Trainer $trainer): static
    {
        return new static(
            id: $trainer->id,
            name: $trainer->name,
            email: $trainer->email,
            document: $trainer->document,
            birth_date: $trainer->birth_date?->format('Y-m-d'),
            phone: $trainer->phone,
            gender: $trainer->gender,
            created_at: $trainer->created_at?->toISOString() ?? '',
        );
    }
}

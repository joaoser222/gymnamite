<?php

namespace App\DTOs\Modalities;

use App\Models\Modality;
use Spatie\LaravelData\Data;

class ModalityResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $visibility,
        public string $created_at,
    ) {}

    public static function fromModel(Modality $modality): static
    {
        return new static(
            id: $modality->id,
            name: $modality->name,
            visibility: $modality->visibility->value,
            created_at: $modality->created_at?->toISOString() ?? '',
        );
    }
}

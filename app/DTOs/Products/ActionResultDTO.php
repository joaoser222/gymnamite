<?php

namespace App\DTOs\Products;

use Spatie\LaravelData\Data;

class ActionResultDTO extends Data
{
    public function __construct(
        public bool $success,
        public mixed $data = null,
        public ?string $message = null,
        public ?array $errors = null,
    ) {}

    public static function success(mixed $data = null, ?string $message = null): static
    {
        return new static(true, $data, $message);
    }
}

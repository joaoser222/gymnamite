<?php

namespace App\DTOs\Receivables;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class ActionResultDTO extends Data
{
    /**
     * @param  array<string, mixed>|null  $errors
     */
    public function __construct(
        #[BooleanType]
        public bool $success,
        public mixed $data = null,
        #[Nullable, StringType]
        public ?string $message = null,
        public ?array $errors = null,
    ) {}

    public static function success(mixed $data = null, ?string $message = null): static
    {
        return new static(true, $data, $message);
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    public static function failure(?string $message = null, ?array $errors = null): static
    {
        return new static(false, null, $message, $errors);
    }
}

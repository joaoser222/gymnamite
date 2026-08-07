<?php

namespace App\DTOs\Contracts;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\LaravelData\Data;

abstract class BaseDTO extends Data
{
    public static function fromRequest(FormRequest $request): static
    {
        return static::from($request->validated());
    }

    public static function fromArray(array $data): static
    {
        return static::from($data);
    }

    public function toActionArray(): array
    {
        return $this->toArray();
    }
}

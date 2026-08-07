<?php

namespace App\DTOs\Purchases;

use App\DTOs\Contracts\BaseDTO;

class CreatePurchaseDTO extends BaseDTO
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(public array $data) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromValidatedData(array $data): static
    {
        return new static($data);
    }
}

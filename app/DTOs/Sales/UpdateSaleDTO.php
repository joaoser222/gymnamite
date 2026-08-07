<?php

namespace App\DTOs\Sales;

use App\DTOs\Contracts\BaseDTO;
use App\Models\Sale;

class UpdateSaleDTO extends BaseDTO
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(public Sale $sale, public array $data) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromValidatedData(Sale $sale, array $data): static
    {
        return new static($sale, $data);
    }
}

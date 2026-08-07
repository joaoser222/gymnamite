<?php

namespace App\DTOs\Purchases;

use App\DTOs\Contracts\BaseDTO;
use App\Models\Purchase;

class UpdatePurchaseDTO extends BaseDTO
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(public Purchase $purchase, public array $data) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromValidatedData(Purchase $purchase, array $data): static
    {
        return new static($purchase, $data);
    }
}

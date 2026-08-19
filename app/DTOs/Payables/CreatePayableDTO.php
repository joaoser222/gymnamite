<?php

namespace App\DTOs\Payables;

use App\DTOs\Contracts\BaseDTO;
use App\Enums\MovementType;
use App\Enums\PaymentMethod;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Integer;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class CreatePayableDTO extends BaseDTO
{
    public function __construct(
        #[Required, Integer, Min(1)]
        public int $supplier_id,

        #[Required, Date]
        public string $due_date,

        #[Required, Numeric, Min(0)]
        public float $total,

        #[Required, Enum(PaymentMethod::class)]
        public string $payment_method,

        #[Required, Enum(MovementType::class)]
        public string $operation_type,

        #[Nullable, StringType, Max(500)]
        public ?string $annotations = null,

        #[Nullable, Integer, Min(1)]
        public ?int $financial_account_id = null,

        #[Nullable, Integer, Min(1)]
        public ?int $financial_category_id = null,
    ) {}
}

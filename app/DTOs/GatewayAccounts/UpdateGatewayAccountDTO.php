<?php

namespace App\DTOs\GatewayAccounts;

use App\DTOs\Contracts\BaseDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\Validation\StringType;

class UpdateGatewayAccountDTO extends BaseDTO
{
    public function __construct(
        #[IntegerType]
        public int $id,

        #[Sometimes, StringType, Max(255)]
        public ?string $name = null,

        #[Nullable, StringType, Max(500)]
        public ?string $description = null,

        #[Sometimes, ArrayType]
        public ?array $settings = null,

        #[BooleanType]
        public ?bool $invoicing_enabled = null,
    ) {}

    public static function fromRequest(FormRequest $request): static
    {
        /** @var Route $route */
        $route = $request->route();

        return static::from([
            ...$request->validated(),
            'id' => (int) $route->parameter('gateway_account')->getKey(),
        ]);
    }
}

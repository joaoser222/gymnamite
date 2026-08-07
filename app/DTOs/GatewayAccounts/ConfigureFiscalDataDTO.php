<?php

namespace App\DTOs\GatewayAccounts;

use App\DTOs\Contracts\BaseDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class ConfigureFiscalDataDTO extends BaseDTO
{
    public function __construct(
        #[IntegerType]
        public int $id,

        #[Required, StringType, Max(100)]
        public string $municipal_service_code,

        #[Required, StringType, Max(5000)]
        public string $service_description,

        #[Nullable, StringType, Max(255)]
        public ?string $municipal_service_name = null,

        #[Nullable, StringType, Max(5000)]
        public ?string $observations = null,

        #[BooleanType]
        public ?bool $incentivized_tax = null,
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

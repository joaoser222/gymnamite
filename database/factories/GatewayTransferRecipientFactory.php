<?php

namespace Database\Factories;

use App\Models\GatewayAccount;
use App\Models\GatewayTransferRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GatewayTransferRecipient>
 */
class GatewayTransferRecipientFactory extends Factory
{
    protected $model = GatewayTransferRecipient::class;

    public function definition(): array
    {
        return [
            'gateway_account_id' => GatewayAccount::factory(),
            'label' => fake()->unique()->company(),
            'holder_name' => fake()->name(),
            'holder_document' => fake()->numerify('###########'),
            'pix_key' => fake()->safeEmail(),
            'pix_key_type' => 'EMAIL',
            'visibility' => 'visible',
        ];
    }
}

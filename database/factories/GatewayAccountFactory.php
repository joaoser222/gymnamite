<?php

namespace Database\Factories;

use App\Models\GatewayAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GatewayAccount>
 */
class GatewayAccountFactory extends Factory
{
    protected $model = GatewayAccount::class;

    public function definition(): array
    {
        return [
            'name' => 'Asaas',
            'description' => 'Asaas',
            'invoicing_enabled' => true,
            'settings' => [
                'api_key' => 'secret-key',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
                'invoicing' => [
                    'service_description' => 'Mensalidade',
                    'municipal_service_code' => '1.01',
                ],
            ],
            'visibility' => 'visible',
        ];
    }

    public function asaasSandbox(): static
    {
        return $this->state(fn (): array => []);
    }
}

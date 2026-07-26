<?php

namespace Tests\Feature;

use App\Enums\Gateway\PostbackStatus;
use App\Models\GatewayAccount;
use App\Models\GatewayPostback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GatewayPostbackReceiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_postback_receive_requires_valid_header_token(): void
    {
        $gatewayAccount = $this->gatewayAccount();

        $response = $this->postJson(route('gateway-postbacks.receive', $gatewayAccount), [
            'event' => 'PAYMENT_CREATED',
            'payment' => ['id' => 'pay_123'],
        ], [
            'asaas-access-token' => 'invalid-token',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('gateway_postbacks', 0);
    }

    public function test_gateway_postback_receive_creates_postback_without_user_authentication(): void
    {
        $gatewayAccount = $this->gatewayAccount();

        $response = $this->postJson(route('gateway-postbacks.receive', $gatewayAccount), [
            'event' => 'PAYMENT_CREATED',
            'payment' => ['id' => 'pay_123'],
        ], [
            'asaas-access-token' => 'valid-token',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('status', PostbackStatus::SUCCESS->value);

        $postback = GatewayPostback::query()->firstOrFail();

        $this->assertSame('PAYMENT_CREATED', $postback->postback_event);
        $this->assertSame('payment', $postback->postback_type);
        $this->assertSame($gatewayAccount->id, $postback->gateway_account_id);
        $this->assertSame(['id' => 'pay_123'], $postback->payload['payment']);
    }

    private function gatewayAccount(): GatewayAccount
    {
        return GatewayAccount::query()->create([
            'name' => 'Asaas',
            'description' => 'Conta principal',
            'settings' => [
                'api_key' => 'secret-api-key',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
                'webhook_token' => 'valid-token',
            ],
            'visibility' => 'visible',
        ]);
    }
}

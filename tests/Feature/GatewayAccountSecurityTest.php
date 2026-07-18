<?php

namespace Tests\Feature;

use App\Models\GatewayAccount;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GatewayAccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function grantPermission(User $user, string $permission): void
    {
        $permission = Permission::query()->create([
            'name' => $permission,
            'description' => $permission,
        ]);

        $user->permissions()->attach($permission);
    }

    public function test_gateway_account_edit_does_not_return_sensitive_settings(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'gateway_accounts.view');

        $gatewayAccount = GatewayAccount::query()->create([
            'name' => 'Asaas',
            'description' => 'Conta principal',
            'settings' => [
                'api_key' => 'secret-api-key',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
                'wallet_id' => 'wallet_123',
            ],
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user)->get(route('gateway-accounts.show', $gatewayAccount));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('gateway_accounts/Details')
            ->where('gateway-account.settings.base_url', 'https://sandbox.asaas.com/api/v3')
            ->where('gateway-account.settings.wallet_id', 'wallet_123')
            ->missing('gateway-account.settings.api_key')
        );
    }

    public function test_gateway_account_update_preserves_sensitive_settings_when_they_are_not_sent(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'gateway_accounts.update');

        $gatewayAccount = GatewayAccount::query()->create([
            'name' => 'Asaas',
            'description' => 'Conta principal',
            'settings' => [
                'api_key' => 'secret-api-key',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
                'wallet_id' => 'wallet_123',
            ],
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user)->put(route('gateway-accounts.update', $gatewayAccount), [
            'name' => 'Asaas',
            'description' => 'Conta atualizada',
            'settings' => [
                'base_url' => 'https://api.asaas.com/api/v3',
                'wallet_id' => 'wallet_456',
            ],
        ]);

        $response->assertRedirect(route('gateway-accounts.index'));

        $this->assertSame([
            'api_key' => 'secret-api-key',
            'base_url' => 'https://api.asaas.com/api/v3',
            'wallet_id' => 'wallet_456',
        ], $gatewayAccount->refresh()->settings);
    }
}

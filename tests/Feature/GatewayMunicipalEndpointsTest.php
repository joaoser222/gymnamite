<?php

namespace Tests\Feature;

use App\Models\GatewayAccount;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GatewayMunicipalEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function grantPermission(User $user, string $permission): void
    {
        $permission = Permission::query()->create([
            'name' => $permission,
            'description' => $permission,
        ]);

        $user->permissions()->attach($permission);
    }

    private function makeAsaasAccount(): GatewayAccount
    {
        return GatewayAccount::query()->create([
            'name' => 'Asaas',
            'description' => 'Conta principal',
            'invoicing_enabled' => true,
            'settings' => [
                'api_key' => 'secret-api-key',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
                'invoicing' => [
                    'service_description' => 'Mensalidade',
                    'municipal_service_code' => '1.01',
                ],
            ],
            'visibility' => 'visible',
        ]);
    }

    public function test_municipal_options_requires_view_permission_and_returns_provider_data(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'gateway_accounts.view');
        $account = $this->makeAsaasAccount();

        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/municipalOptions' => Http::response([
                'municipalOptions' => [
                    ['code' => '1.01', 'name' => 'Serviço de manutenção'],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->getJson(route('gateway-accounts.invoicing.municipal-options', $account));

        $response->assertOk();
        $response->assertJson([
            'municipalOptions' => [
                ['code' => '1.01', 'name' => 'Serviço de manutenção'],
            ],
        ]);
    }

    public function test_municipal_options_requires_view_permission(): void
    {
        $user = User::factory()->create();
        $account = $this->makeAsaasAccount();

        Http::preventStrayRequests();

        $this->actingAs($user)->getJson(route('gateway-accounts.invoicing.municipal-options', $account))->assertForbidden();
    }

    public function test_municipal_services_forwards_filters_to_provider(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'gateway_accounts.view');
        $account = $this->makeAsaasAccount();

        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/municipalServices*' => Http::response([
                'items' => [
                    ['code' => '1.01', 'description' => 'Mensalidade'],
                ],
            ], 200),
        ]);

        $url = route('gateway-accounts.invoicing.municipal-services', $account).'?'.http_build_query([
            'city' => 'São Paulo',
            'state' => 'SP',
            'service_code' => '1.01',
            'description' => 'Mensalidade',
        ]);

        $response = $this->actingAs($user)->getJson($url);

        $response->assertOk();
        $response->assertJson([
            'items' => [
                ['code' => '1.01', 'description' => 'Mensalidade'],
            ],
        ]);

        Http::assertSent(function ($request): bool {
            if (! str_starts_with($request->url(), 'https://sandbox.asaas.com/api/v3/invoices/municipalServices')) {
                return false;
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $query['city'] === 'São Paulo'
                && $query['state'] === 'SP'
                && $query['service_code'] === '1.01'
                && $query['description'] === 'Mensalidade';
        });
    }

    public function test_configure_fiscal_data_requires_update_permission_and_persists_configuration(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'gateway_accounts.update');
        $account = $this->makeAsaasAccount();

        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/municipalConfiguration' => Http::response([
                'municipalServiceCode' => '1.02',
                'serviceDescription' => 'Mensalidade mensal',
                'status' => 'OK',
            ], 200),
        ]);

        $response = $this->actingAs($user)->putJson(
            route('gateway-accounts.invoicing.municipal-configuration', $account),
            [
                'municipal_service_code' => '1.02',
                'service_description' => 'Mensalidade mensal',
            ],
        );

        $response->assertOk();
        $response->assertJsonPath('invoicing_configured', true);

        $fresh = $account->fresh();
        $this->assertSame('1.02', data_get($fresh->settings, 'invoicing.municipal_service_code'));
        $this->assertSame('Mensalidade mensal', data_get($fresh->settings, 'invoicing.service_description'));
        $this->assertNotNull(data_get($fresh->settings, 'invoicing.fiscal_configuration_at'));
    }

    public function test_configure_fiscal_data_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'gateway_accounts.update');
        $account = $this->makeAsaasAccount();

        Http::preventStrayRequests();

        $response = $this->actingAs($user)->putJson(
            route('gateway-accounts.invoicing.municipal-configuration', $account),
            [],
        );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['municipal_service_code', 'service_description']);
        Http::assertNothingSent();
    }

    public function test_configure_fiscal_data_rejects_provider_without_invoicing_support(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'gateway_accounts.update');
        $manual = GatewayAccount::query()->create([
            'name' => 'Manual',
            'description' => 'Provedor sem suporte fiscal',
            'invoicing_enabled' => true,
            'settings' => [
                'api_key' => 'secret-key',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
            ],
            'visibility' => 'visible',
        ]);

        Http::preventStrayRequests();

        $response = $this->actingAs($user)->putJson(
            route('gateway-accounts.invoicing.municipal-configuration', $manual),
            [
                'municipal_service_code' => '1.01',
                'service_description' => 'Mensalidade',
            ],
        );

        $response->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_configure_fiscal_data_response_does_not_leak_sensitive_settings(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'gateway_accounts.update');
        $account = $this->makeAsaasAccount();

        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/municipalConfiguration' => Http::response([
                'municipalServiceCode' => '1.01',
                'serviceDescription' => 'Mensalidade',
                'status' => 'OK',
            ], 200),
        ]);

        $response = $this->actingAs($user)->putJson(
            route('gateway-accounts.invoicing.municipal-configuration', $account),
            [
                'municipal_service_code' => '1.01',
                'service_description' => 'Mensalidade',
            ],
        );

        $response->assertOk();
        $this->assertSame(
            ['configuration', 'invoicing_configured', 'invoicing_supported'],
            array_keys($response->json()),
        );
        $response->assertJsonMissing(['api_key' => 'secret-api-key']);
        $response->assertJsonMissing(['base_url' => 'https://sandbox.asaas.com/api/v3']);
    }
}

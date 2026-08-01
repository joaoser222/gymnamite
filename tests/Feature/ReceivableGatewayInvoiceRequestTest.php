<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\GatewayAccount;
use App\Models\GatewayPayment;
use App\Models\GatewayPostback;
use App\Models\Permission;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReceivableGatewayInvoiceRequestTest extends TestCase
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

    private function makeEligibleAccount(): GatewayAccount
    {
        return GatewayAccount::query()->create([
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
        ]);
    }

    /**
     * @return array{0: GatewayAccount, 1: Receivable, 2: GatewayPayment}
     */
    private function makeReceivableWithPayment(): array
    {
        $account = $this->makeEligibleAccount();

        $client = Client::factory()->create();
        $receivable = Receivable::query()->create([
            'operation_type' => OperationType::RECEIVABLE,
            'invoice_type' => 'standard',
            'due_date' => now()->addDays(10)->toDateString(),
            'payment_method' => PaymentMethod::PIX,
            'gross_value' => 100,
            'status' => InvoiceStatus::WAITING,
            'holder_id' => $client->id,
            'holder_type' => 'client',
            'visibility' => 'visible',
        ]);
        $postback = GatewayPostback::query()->create([
            'postback_event' => 'PAYMENT_CREATED',
            'postback_type' => 'payment',
            'payload' => [],
            'status' => 'success',
            'gateway_account_id' => $account->id,
        ]);
        $customer = $account->customers()->create([
            'gateway_reference_key' => 'cus_123',
            'holder_id' => $client->id,
            'holder_type' => 'client',
        ]);
        $payment = GatewayPayment::query()->create([
            'gateway_reference_key' => 'pay_123',
            'payment_method' => PaymentMethod::PIX,
            'payment_date' => now()->toDateString(),
            'status' => 'pending',
            'gross_value' => 100,
            'fee_value' => 0,
            'gateway_account_id' => $account->id,
            'gateway_customer_id' => $customer->id,
            'gateway_postback_id' => $postback->id,
            'invoice_id' => $receivable->id,
        ]);

        return [$account, $receivable, $payment];
    }

    private function makeReceivableWithoutPayment(): Receivable
    {
        $client = Client::factory()->create();

        return Receivable::query()->create([
            'operation_type' => OperationType::RECEIVABLE,
            'invoice_type' => 'standard',
            'due_date' => now()->addDays(10)->toDateString(),
            'payment_method' => PaymentMethod::PIX,
            'gross_value' => 100,
            'status' => InvoiceStatus::WAITING,
            'holder_id' => $client->id,
            'holder_type' => 'client',
            'visibility' => 'visible',
        ]);
    }

    public function test_request_gateway_invoice_creates_gateway_invoice_and_returns_201(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'receivables.request_invoice');

        [, $receivable] = $this->makeReceivableWithPayment();

        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices' => Http::response([
                'id' => 'inv_456',
                'status' => 'AUTHORIZED',
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson(route('receivables.request-gateway-invoice', $receivable));

        $response->assertStatus(201);
        $response->assertJsonPath('gateway_reference_key', 'inv_456');
        $response->assertJsonPath('status', 'authorized');

        $this->assertDatabaseHas('gateway_invoices', [
            'gateway_reference_key' => 'inv_456',
            'invoice_id' => $receivable->id,
            'status' => 'authorized',
        ]);
    }

    public function test_request_gateway_invoice_returns_422_when_receivable_has_no_gateway_payment(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'receivables.request_invoice');

        $receivable = $this->makeReceivableWithoutPayment();

        Http::preventStrayRequests();

        $response = $this->actingAs($user)->postJson(route('receivables.request-gateway-invoice', $receivable));

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Este recebimento não possui pagamento do gateway.',
        ]);
    }

    public function test_request_gateway_invoice_requires_permission(): void
    {
        $user = User::factory()->create();

        [, $receivable] = $this->makeReceivableWithPayment();

        Http::preventStrayRequests();

        $this->actingAs($user)
            ->postJson(route('receivables.request-gateway-invoice', $receivable))
            ->assertForbidden();
    }
}

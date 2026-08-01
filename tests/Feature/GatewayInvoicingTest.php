<?php

namespace Tests\Feature;

use App\AccessControl\AccessRole;
use App\AccessControl\RolePermissionMap;
use App\Enums\Gateway\InvoiceStatus;
use App\Enums\InvoiceStatus as InternalInvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\GatewayAccount;
use App\Models\GatewayPayment;
use App\Models\GatewayPostback;
use App\Models\Invoice;
use App\PaymentGateways\Adapters\AsaasPaymentGatewayAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GatewayInvoicingTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_invoice_module_is_read_only_for_administrators(): void
    {
        $permissions = (new RolePermissionMap)->getMap();

        $this->assertSame(['view'], $permissions[AccessRole::ADMINISTRATOR->value]['gateway_invoices']);
    }

    public function test_asaas_invoice_request_persists_the_fiscal_response(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices' => Http::response([
                'id' => 'inv_123',
                'status' => 'AUTHORIZED',
            ], 200),
        ]);

        $account = GatewayAccount::query()->create([
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

        $client = Client::factory()->create();
        $invoice = Invoice::query()->create([
            'operation_type' => OperationType::RECEIVABLE,
            'invoice_type' => InvoiceType::STANDARD,
            'due_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::PIX,
            'gross_value' => 100,
            'status' => InternalInvoiceStatus::WAITING,
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
            'invoice_id' => $invoice->id,
        ]);

        $gatewayInvoice = (new AsaasPaymentGatewayAdapter($account))->requestInvoice($payment, [
            'service_description' => 'Mensalidade',
            'municipal_service_code' => '1.01',
        ]);

        $this->assertSame('inv_123', $gatewayInvoice->gateway_reference_key);
        $this->assertSame(InvoiceStatus::AUTHORIZED, $gatewayInvoice->status);
        $this->assertDatabaseHas('gateway_invoices', [
            'gateway_reference_key' => 'inv_123',
            'invoice_id' => $invoice->id,
        ]);
        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox.asaas.com/api/v3/invoices'
            && $request['payment'] === 'pay_123'
            && $request['municipalServiceCode'] === '1.01');
    }

    public function test_invoice_webhook_updates_the_invoice_and_is_idempotent(): void
    {
        $account = GatewayAccount::query()->create([
            'name' => 'Asaas', 'description' => 'Asaas', 'settings' => ['webhook_token' => 'token'],
            'visibility' => 'visible',
        ]);

        $payload = [
            'id' => 'event_123',
            'event' => 'INVOICE_AUTHORIZED',
            'invoice' => ['id' => 'inv_123', 'status' => 'AUTHORIZED'],
        ];

        (new AsaasPaymentGatewayAdapter($account))->processPostback($payload);
        (new AsaasPaymentGatewayAdapter($account))->processPostback($payload);

        $this->assertDatabaseCount('gateway_postbacks', 1);
        $this->assertDatabaseHas('gateway_postbacks', ['external_event_key' => 'event_123']);
    }
}

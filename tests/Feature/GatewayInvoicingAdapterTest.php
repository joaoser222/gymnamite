<?php

namespace Tests\Feature;

use App\Enums\Gateway\InvoiceStatus;
use App\Enums\InvoiceStatus as InternalInvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\GatewayAccount;
use App\Models\GatewayInvoice;
use App\Models\GatewayPayment;
use App\Models\GatewayPostback;
use App\Models\Invoice;
use App\PaymentGateways\Adapters\AsaasPaymentGatewayAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GatewayInvoicingAdapterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: GatewayAccount, 1: GatewayInvoice}
     */
    private function makeInvoice(string $referenceKey = 'inv_123', InvoiceStatus $status = InvoiceStatus::PENDING): array
    {
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

        $gatewayInvoice = GatewayInvoice::query()->create([
            'gateway_account_id' => $account->id,
            'gateway_payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'gateway_reference_key' => $referenceKey,
            'status' => $status,
        ]);

        return [$account, $gatewayInvoice];
    }

    public function test_schedule_invoice_posts_to_schedule_endpoint_and_persists_response(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_123/schedule' => Http::response([
                'id' => 'inv_123',
                'status' => 'AUTHORIZED',
            ], 200),
        ]);

        [, $gatewayInvoice] = $this->makeInvoice();

        $updated = (new AsaasPaymentGatewayAdapter($gatewayInvoice->gatewayAccount))->scheduleInvoice($gatewayInvoice);

        $this->assertSame('inv_123', $updated->gateway_reference_key);
        $this->assertSame(InvoiceStatus::AUTHORIZED, $updated->status);
        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox.asaas.com/api/v3/invoices/inv_123/schedule');
    }

    public function test_find_invoice_returns_array_on_success(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_123' => Http::response([
                'id' => 'inv_123',
                'status' => 'AUTHORIZED',
            ], 200),
        ]);

        [, $gatewayInvoice] = $this->makeInvoice();

        $body = (new AsaasPaymentGatewayAdapter($gatewayInvoice->gatewayAccount))->findInvoice($gatewayInvoice);

        $this->assertIsArray($body);
        $this->assertSame('inv_123', $body['id']);
    }

    public function test_find_invoice_returns_null_on_404(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_123' => Http::response([], 404),
        ]);

        [, $gatewayInvoice] = $this->makeInvoice();

        $this->assertNull((new AsaasPaymentGatewayAdapter($gatewayInvoice->gatewayAccount))->findInvoice($gatewayInvoice));
    }

    public function test_sync_invoice_without_force_does_not_rewrite_when_status_unchanged(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_123' => Http::response([
                'id' => 'inv_123',
                'status' => 'AUTHORIZED',
                'serviceDescription' => 'Mensalidade',
            ], 200),
        ]);

        [, $gatewayInvoice] = $this->makeInvoice('inv_123', InvoiceStatus::AUTHORIZED);

        $synced = (new AsaasPaymentGatewayAdapter($gatewayInvoice->gatewayAccount))->syncInvoice($gatewayInvoice);

        $this->assertSame(InvoiceStatus::AUTHORIZED, $synced->status);
        $this->assertNull($synced->payload);
    }

    public function test_sync_invoice_updates_when_provider_status_changed(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_123' => Http::response([
                'id' => 'inv_123',
                'status' => 'AUTHORIZED',
                'serviceDescription' => 'Mensalidade',
            ], 200),
        ]);

        [, $gatewayInvoice] = $this->makeInvoice('inv_123', InvoiceStatus::PENDING);

        $synced = (new AsaasPaymentGatewayAdapter($gatewayInvoice->gatewayAccount))->syncInvoice($gatewayInvoice);

        $this->assertSame(InvoiceStatus::AUTHORIZED, $synced->status);
        $this->assertSame('Mensalidade', $synced->service_description);
        $this->assertSame('AUTHORIZED', $synced->payload['status']);
    }

    public function test_sync_invoice_with_force_rewrites_even_when_unchanged(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_123' => Http::response([
                'id' => 'inv_123',
                'status' => 'AUTHORIZED',
                'serviceDescription' => 'Mensalidade',
            ], 200),
        ]);

        [, $gatewayInvoice] = $this->makeInvoice('inv_123', InvoiceStatus::AUTHORIZED);

        $synced = (new AsaasPaymentGatewayAdapter($gatewayInvoice->gatewayAccount))->syncInvoice($gatewayInvoice, true);

        $this->assertSame(InvoiceStatus::AUTHORIZED, $synced->status);
        $this->assertSame('Mensalidade', $synced->service_description);
        $this->assertSame('Mensalidade', $synced->payload['serviceDescription']);
    }

    public function test_sync_invoice_returns_null_when_provider_returns_404(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_123' => Http::response([], 404),
        ]);

        [, $gatewayInvoice] = $this->makeInvoice();

        $this->assertNull((new AsaasPaymentGatewayAdapter($gatewayInvoice->gatewayAccount))->syncInvoice($gatewayInvoice));
    }

    public function test_authorize_invoice_marks_invoice_as_authorized(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_123/authorize' => Http::response([
                'id' => 'inv_123',
                'status' => 'AUTHORIZED',
            ], 200),
        ]);

        [, $gatewayInvoice] = $this->makeInvoice();

        $updated = (new AsaasPaymentGatewayAdapter($gatewayInvoice->gatewayAccount))->authorizeInvoice($gatewayInvoice);

        $this->assertSame(InvoiceStatus::AUTHORIZED, $updated->status);
        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox.asaas.com/api/v3/invoices/inv_123/authorize');
    }

    public function test_cancel_invoice_without_reason_sends_delete(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_123' => Http::response([
                'id' => 'inv_123',
                'status' => 'CANCELED',
            ], 200),
        ]);

        [, $gatewayInvoice] = $this->makeInvoice();

        $updated = (new AsaasPaymentGatewayAdapter($gatewayInvoice->gatewayAccount))->cancelInvoice($gatewayInvoice);

        $this->assertSame(InvoiceStatus::CANCELED, $updated->status);
        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox.asaas.com/api/v3/invoices/inv_123'
            && $request->method() === 'DELETE'
            && ! isset($request['reason']));
    }

    public function test_cancel_invoice_sends_reason_when_provided(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_123' => Http::response([
                'id' => 'inv_123',
                'status' => 'CANCELED',
            ], 200),
        ]);

        [, $gatewayInvoice] = $this->makeInvoice();

        (new AsaasPaymentGatewayAdapter($gatewayInvoice->gatewayAccount))->cancelInvoice($gatewayInvoice, 'Motivo do cancelamento');

        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox.asaas.com/api/v3/invoices/inv_123'
            && $request->method() === 'DELETE'
            && $request['reason'] === 'Motivo do cancelamento');
    }

    public function test_get_municipal_options_returns_provider_array(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/municipalOptions' => Http::response([
                'municipalOptions' => [
                    ['code' => '1.01', 'name' => 'Serviço de manutenção'],
                ],
            ], 200),
        ]);

        $account = $this->makeInvoice()[0];

        $result = (new AsaasPaymentGatewayAdapter($account))->getMunicipalOptions();

        $this->assertSame('1.01', $result['municipalOptions'][0]['code']);
        $this->assertSame('Serviço de manutenção', $result['municipalOptions'][0]['name']);
    }

    public function test_get_municipal_services_forwards_filters(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/municipalServices*' => Http::response(['items' => []], 200),
        ]);

        $account = $this->makeInvoice()[0];

        (new AsaasPaymentGatewayAdapter($account))->getMunicipalServices([
            'city' => 'São Paulo',
            'state' => 'SP',
            'service_code' => '1.01',
            'description' => 'Mensalidade',
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

    public function test_configure_fiscal_data_puts_payload(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/municipalConfiguration' => Http::response([
                'municipalServiceCode' => '1.01',
                'status' => 'OK',
            ], 200),
        ]);

        $account = $this->makeInvoice()[0];

        $result = (new AsaasPaymentGatewayAdapter($account))->configureFiscalData([
            'municipalServiceCode' => '1.01',
            'serviceDescription' => 'Mensalidade',
        ]);

        $this->assertSame('1.01', $result['municipalServiceCode']);
        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox.asaas.com/api/v3/invoices/municipalConfiguration'
            && $request->method() === 'PUT'
            && $request['municipalServiceCode'] === '1.01'
            && $request['serviceDescription'] === 'Mensalidade');
    }
}

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncFiscalInvoicesCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: GatewayAccount, 1: GatewayInvoice}
     */
    private function makeEligibleInvoice(string $referenceKey, InvoiceStatus $status): array
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
            'gateway_reference_key' => 'cus_'.$referenceKey,
            'holder_id' => $client->id,
            'holder_type' => 'client',
        ]);
        $payment = GatewayPayment::query()->create([
            'gateway_reference_key' => 'pay_'.$referenceKey,
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

    public function test_command_succeeds_when_no_eligible_accounts(): void
    {
        $this->artisan('gateway:sync-fiscal-invoices')
            ->expectsOutputToContain('No eligible gateway accounts found')
            ->assertExitCode(0);
    }

    public function test_command_reports_found_and_updated_and_succeeds(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_123' => Http::response([
                'id' => 'inv_123',
                'status' => 'AUTHORIZED',
            ], 200),
        ]);

        [$account, $gatewayInvoice] = $this->makeEligibleInvoice('inv_123', InvoiceStatus::PENDING);

        $this->artisan('gateway:sync-fiscal-invoices', ['--account' => [$account->id]])
            ->expectsOutputToContain('Account #'.$account->id)
            ->expectsOutputToContain('Notes found')
            ->expectsOutputToContain('Notes updated')
            ->assertExitCode(0);

        $this->assertSame(InvoiceStatus::AUTHORIZED, $gatewayInvoice->fresh()->status);
    }

    public function test_command_fails_when_account_has_failed_invoices(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_123' => Http::response([], 500),
        ]);

        [$account] = $this->makeEligibleInvoice('inv_123', InvoiceStatus::PENDING);

        $this->artisan('gateway:sync-fiscal-invoices', ['--account' => [$account->id]])
            ->expectsOutputToContain('Notes failed')
            ->assertExitCode(1);
    }
}

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
use App\Services\Gateway\FiscalSyncOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FiscalSyncOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private function service(): FiscalSyncOrchestrator
    {
        return app(FiscalSyncOrchestrator::class);
    }

    private function makeAccount(): GatewayAccount
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
     * @return array{0: GatewayAccount, 1: GatewayInvoice}
     */
    private function makeEligibleInvoice(string $referenceKey, InvoiceStatus $status, ?GatewayAccount $account = null): array
    {
        $account ??= $this->makeAccount();

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

    public function test_sync_all_updates_invoice_when_provider_status_changed(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_123' => Http::response([
                'id' => 'inv_123',
                'status' => 'AUTHORIZED',
            ], 200),
        ]);

        [$account, $gatewayInvoice] = $this->makeEligibleInvoice('inv_123', InvoiceStatus::PENDING);

        $results = $this->service()->syncAll();

        $this->assertSame(1, $results[$account->id]['found']);
        $this->assertSame(1, $results[$account->id]['updated']);
        $this->assertSame(0, $results[$account->id]['unchanged']);
        $this->assertSame(InvoiceStatus::AUTHORIZED, $gatewayInvoice->fresh()->status);
    }

    public function test_sync_all_counts_unchanged_without_force(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_123' => Http::response([
                'id' => 'inv_123',
                'status' => 'AUTHORIZED',
                'serviceDescription' => 'Mensalidade',
            ], 200),
        ]);

        [$account, $gatewayInvoice] = $this->makeEligibleInvoice('inv_123', InvoiceStatus::AUTHORIZED);

        $results = $this->service()->syncAll();

        $this->assertSame(1, $results[$account->id]['unchanged']);
        $this->assertSame(0, $results[$account->id]['updated']);
        $this->assertNull($gatewayInvoice->fresh()->payload);
    }

    public function test_sync_all_with_force_rewrites_unchanged_invoice(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_123' => Http::response([
                'id' => 'inv_123',
                'status' => 'AUTHORIZED',
                'serviceDescription' => 'Mensalidade',
            ], 200),
        ]);

        [$account, $gatewayInvoice] = $this->makeEligibleInvoice('inv_123', InvoiceStatus::AUTHORIZED);

        $results = $this->service()->syncAll([], [], true);

        // O contador do serviço compara o status final: com status inalterado o
        // registro entra em 'unchanged', porém o --force regrava os dados locais.
        $this->assertSame(0, $results[$account->id]['updated']);
        $this->assertSame(1, $results[$account->id]['unchanged']);
        $this->assertSame('Mensalidade', $gatewayInvoice->fresh()->service_description);
    }

    public function test_sync_all_marks_not_found_when_provider_returns_404(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_123' => Http::response([], 404),
        ]);

        [$account, $gatewayInvoice] = $this->makeEligibleInvoice('inv_123', InvoiceStatus::PENDING);

        $results = $this->service()->syncAll();

        $this->assertSame(1, $results[$account->id]['updated']);

        $fresh = $gatewayInvoice->fresh();
        $this->assertSame(InvoiceStatus::ERROR, $fresh->status);
        $this->assertSame('not found on provider', $fresh->error_message);
        $this->assertSame('Nota não encontrada no provedor', $fresh->status_description);
    }

    public function test_sync_all_keeps_terminal_status_on_404(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_canceled' => Http::response([], 404),
            'https://sandbox.asaas.com/api/v3/invoices/inv_error' => Http::response([], 404),
            'https://sandbox.asaas.com/api/v3/invoices/inv_unknown' => Http::response([], 404),
        ]);

        $account = $this->makeAccount();
        $cases = [
            'inv_canceled' => InvoiceStatus::CANCELED,
            'inv_error' => InvoiceStatus::ERROR,
            'inv_unknown' => InvoiceStatus::UNKNOWN,
        ];
        $invoices = [];

        foreach ($cases as $referenceKey => $status) {
            $invoices[$referenceKey] = $this->makeEligibleInvoice($referenceKey, $status, $account)[1];
        }

        $results = $this->service()->syncAll();

        $this->assertSame(3, $results[$account->id]['found']);
        $this->assertSame(3, $results[$account->id]['updated']);

        foreach ($cases as $referenceKey => $status) {
            $fresh = $invoices[$referenceKey]->fresh();
            $this->assertSame($status, $fresh->status);
            $this->assertNull($fresh->error_message);
        }
    }

    public function test_sync_all_respects_account_filter(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_a' => Http::response([
                'id' => 'inv_a',
                'status' => 'AUTHORIZED',
            ], 200),
            'https://sandbox.asaas.com/api/v3/invoices/inv_b' => Http::response([
                'id' => 'inv_b',
                'status' => 'AUTHORIZED',
            ], 200),
        ]);

        [$accountA] = $this->makeEligibleInvoice('inv_a', InvoiceStatus::PENDING);
        [$accountB] = $this->makeEligibleInvoice('inv_b', InvoiceStatus::PENDING);

        $results = $this->service()->syncAll([$accountA->id]);

        $this->assertArrayHasKey($accountA->id, $results);
        $this->assertArrayNotHasKey($accountB->id, $results);
        $this->assertSame(1, $results[$accountA->id]['found']);
    }

    public function test_sync_all_respects_status_filter(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://sandbox.asaas.com/api/v3/invoices/inv_pending' => Http::response([
                'id' => 'inv_pending',
                'status' => 'AUTHORIZED',
            ], 200),
            'https://sandbox.asaas.com/api/v3/invoices/inv_authorized' => Http::response([
                'id' => 'inv_authorized',
                'status' => 'AUTHORIZED',
            ], 200),
        ]);

        $account = $this->makeAccount();
        [, $authorizedInvoice] = $this->makeEligibleInvoice('inv_authorized', InvoiceStatus::AUTHORIZED, $account);
        [, $pendingInvoice] = $this->makeEligibleInvoice('inv_pending', InvoiceStatus::PENDING, $account);

        $results = $this->service()->syncAll([], [InvoiceStatus::PENDING->value]);

        $this->assertSame(1, $results[$account->id]['found']);
        $this->assertSame(1, $results[$account->id]['updated']);
        $this->assertSame(InvoiceStatus::AUTHORIZED, $authorizedInvoice->fresh()->status);
        $this->assertSame(InvoiceStatus::AUTHORIZED, $pendingInvoice->fresh()->status);
    }

    public function test_sync_all_skips_ineligible_accounts(): void
    {
        Http::preventStrayRequests();

        GatewayAccount::query()->create([
            'name' => 'Asaas',
            'description' => 'Desabilitada',
            'invoicing_enabled' => false,
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
        GatewayAccount::query()->create([
            'name' => 'Asaas',
            'description' => 'Sem configuração municipal',
            'invoicing_enabled' => true,
            'settings' => [
                'api_key' => 'secret-key',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
            ],
            'visibility' => 'visible',
        ]);
        GatewayAccount::query()->create([
            'name' => 'Manual',
            'description' => 'Provedor sem suporte fiscal',
            'invoicing_enabled' => true,
            'settings' => [
                'invoicing' => [
                    'service_description' => 'Mensalidade',
                    'municipal_service_code' => '1.01',
                ],
            ],
            'visibility' => 'visible',
        ]);

        $this->assertSame([], $this->service()->syncAll());
    }
}

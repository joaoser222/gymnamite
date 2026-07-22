<?php

namespace Tests\Feature;

use App\Enums\BillableStatus;
use App\Enums\Gateway\TransactionStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\GatewayAccount;
use App\Models\GatewayCustomer;
use App\Models\GatewayPayment;
use App\Models\Invoice;
use App\Models\Sale;
use App\Services\BillingInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class InvoiceStatusLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_invoice_starts_pending_and_becomes_waiting_when_gateway_payment_is_created(): void
    {
        $client = Client::factory()->create();
        $sale = Sale::query()->create([
            'gross_value' => 100,
            'discount_value' => 0,
            'total' => 100,
            'status' => BillableStatus::OPEN,
            'payment_method' => PaymentMethod::PIX,
            'first_due_date' => '2026-07-20',
            'installments' => 1,
            'visibility' => 'visible',
            'client_id' => $client->id,
        ]);

        $invoice = app(BillingInvoiceService::class)->generate($sale)->first();

        $this->assertSame(InvoiceStatus::PENDING, $invoice->status);

        $gatewayAccount = GatewayAccount::query()->create([
            'name' => 'Asaas',
            'description' => 'Asaas',
            'settings' => [],
        ]);
        $gatewayCustomer = GatewayCustomer::query()->create([
            'gateway_reference_key' => 'cus_123',
            'holder_id' => $client->id,
            'holder_type' => 'client',
            'gateway_account_id' => $gatewayAccount->id,
        ]);

        GatewayPayment::query()->create([
            'gateway_reference_key' => 'pay_123',
            'payment_method' => PaymentMethod::PIX,
            'status' => TransactionStatus::PENDING,
            'gross_value' => 100,
            'fee_value' => 0,
            'gateway_account_id' => $gatewayAccount->id,
            'gateway_customer_id' => $gatewayCustomer->id,
            'invoice_id' => $invoice->id,
        ]);

        $this->assertSame(InvoiceStatus::WAITING, $invoice->refresh()->status);
    }

    public function test_only_pending_cash_invoices_are_marked_overdue_after_due_date(): void
    {
        Date::setTestNow('2026-07-20');

        $client = Client::factory()->create();
        $sale = Sale::query()->create([
            'gross_value' => 100,
            'discount_value' => 0,
            'total' => 100,
            'status' => BillableStatus::OPEN,
            'payment_method' => PaymentMethod::CASH,
            'visibility' => 'visible',
            'client_id' => $client->id,
        ]);

        $cashInvoice = $this->invoice($client, $sale, PaymentMethod::CASH, '2026-07-19');
        $pixInvoice = $this->invoice($client, $sale, PaymentMethod::PIX, '2026-07-19');

        $this->artisan('invoices:mark-overdue-cash')->assertSuccessful();

        $this->assertSame(InvoiceStatus::OVERDUED, $cashInvoice->refresh()->status);
        $this->assertSame(InvoiceStatus::PENDING, $pixInvoice->refresh()->status);

        Date::setTestNow();
    }

    private function invoice(Client $client, Sale $sale, PaymentMethod $paymentMethod, string $dueDate): Invoice
    {
        return Invoice::query()->create([
            'operation_type' => OperationType::RECEIVABLE,
            'invoice_type' => 'standard',
            'due_date' => $dueDate,
            'payment_method' => $paymentMethod,
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => InvoiceStatus::PENDING,
            'visibility' => 'visible',
            'holder_id' => $client->id,
            'holder_type' => 'client',
            'billable_id' => $sale->id,
            'billable_type' => 'sale',
        ]);
    }
}

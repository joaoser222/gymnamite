<?php

namespace App\Services\Gateway;

use App\Contracts\BillingInvoiceSource;
use App\Enums\InvoiceStatus;
use App\Enums\OperationType;
use App\Models\Invoice;
use App\PaymentGateways\Contracts\PaymentGatewayAdapter;
use App\Repositories\Contracts\GatewayPaymentRepositoryInterface;
use App\Services\Billing\InvoiceGenerator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class GatewayBillingOrchestrator
{
    public function __construct(
        private readonly InvoiceGenerator $invoiceGenerator,
        private readonly PaymentGatewayAdapter $gateway,
        private readonly GatewayPaymentRepositoryInterface $gatewayPaymentRepository,
    ) {}

    /**
     * Generate invoices and attempt to sync with gateway.
     *
     * @return Collection<int, Invoice>
     */
    public function generateAndSync(BillingInvoiceSource&Model $source): Collection
    {
        $invoices = $this->invoiceGenerator->generate($source);

        try {
            $this->syncInvoices($invoices);
        } catch (\Throwable $e) {
            report($e);
        }

        return $invoices;
    }

    /**
     * Sync pending invoices with gateway.
     */
    public function syncPendingInvoices(): int
    {
        $synced = 0;

        foreach ($this->invoicesEligibleForSync() as $invoice) {
            if ($this->syncInvoice($invoice)) {
                $synced++;
            }
        }

        return $synced;
    }

    /**
     * @return Collection<int, Invoice>
     */
    private function invoicesEligibleForSync(): Collection
    {
        return Invoice::query()
            ->where('operation_type', OperationType::RECEIVABLE)
            ->where('uses_gateway_payment_method', true)
            ->where('should_generate_gateway_transaction', true)
            ->where('status', InvoiceStatus::PENDING)
            ->whereDoesntHave('gatewayPayment')
            ->get();
    }

    public function syncInvoice(Invoice $invoice): bool
    {
        $source = $invoice->billable;

        if ($invoice->holder === null) {
            return false;
        }

        if ($invoice->operation_type !== OperationType::RECEIVABLE) {
            return false;
        }

        if (! $invoice->usesGatewayPaymentMethod()) {
            return false;
        }

        if (! $invoice->shouldGenerateGatewayTransaction()) {
            return false;
        }

        if ($this->gatewayPaymentRepository->existsWhere(['invoice_id' => $invoice->id])) {
            return false;
        }

        $customer = $this->gateway->createCustomer(
            $source instanceof BillingInvoiceSource && $source instanceof Model
                ? $source->billingHolder()
                : $invoice->holder,
        );

        $this->gateway->createPayment($invoice, $customer, [
            'description' => $this->buildDescription($source, $invoice),
        ]);

        $invoice->update([
            'status' => InvoiceStatus::WAITING,
        ]);

        return true;
    }

    private function buildDescription(?Model $source, Invoice $invoice): string
    {
        if (! $source instanceof BillingInvoiceSource) {
            return "Fatura #{$invoice->getKey()}";
        }

        $className = class_basename($source::class);

        return match ($className) {
            'Contract' => "Contrato #{$source->getKey()}",
            'DirectLesson' => "Aula avulsa #{$source->getKey()}",
            'Sale' => "Venda #{$source->getKey()}",
            'Purchase' => "Compra #{$source->getKey()}",
            default => "Fatura #{$source->getKey()}",
        };
    }
}

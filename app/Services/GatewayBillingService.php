<?php

namespace App\Services;

use App\Contracts\BillingInvoiceSource;
use App\Enums\InvoiceStatus;
use App\Enums\OperationType;
use App\Models\GatewayPayment;
use App\Models\Invoice;
use App\PaymentGateways\Contracts\PaymentGatewayAdapter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class GatewayBillingService
{
    public function __construct(
        private readonly BillingInvoiceService $billingInvoiceService,
        private readonly PaymentGatewayAdapter $gateway,
    ) {}

    public function generate(BillingInvoiceSource&Model $source): Collection
    {
        $invoices = $this->billingInvoiceService->generate($source);

        try {
            $this->syncInvoices($invoices);
        } catch (\Throwable $e) {
            report($e);
        }

        return $invoices;
    }

    public function syncInvoices(Collection $invoices): int
    {
        $synced = 0;

        foreach ($invoices as $invoice) {
            if ($this->syncInvoice($invoice)) {
                $synced++;
            }
        }

        return $synced;
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

        if (GatewayPayment::query()->where('invoice_id', $invoice->id)->exists()) {
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

    public function resolveDiscountInstallments(BillingInvoiceSource $source, array $grossValueInstallments): array
    {
        return $this->billingInvoiceService->resolveDiscountInstallments($source, $grossValueInstallments);
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

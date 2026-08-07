<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Models\GatewayPayment;
use App\Models\Invoice;
use App\Services\Gateway\GatewayBillingOrchestrator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

#[Signature('gateway:sync-invoices {--invoice=* : Invoice IDs to sync with the payment gateway}')]
#[Description('Synchronize eligible receivable invoices with the configured payment gateway')]
class SyncGatewayInvoices extends Command
{
    public function handle(GatewayBillingOrchestrator $gatewayBillingOrchestrator): int
    {
        $invoices = $this->invoices();

        if ($invoices->isEmpty()) {
            $this->components->info('No eligible invoices found for gateway synchronization.');

            return self::SUCCESS;
        }

        $synced = 0;
        $failed = 0;

        foreach ($invoices as $invoice) {
            try {
                if ($gatewayBillingOrchestrator->syncInvoice($invoice)) {
                    $synced++;
                }
            } catch (\Throwable $e) {
                report($e);

                $failed++;
                $this->components->error("Invoice #{$invoice->id} failed: {$e->getMessage()}");
            }
        }

        $this->components->twoColumnDetail('Invoices found', (string) $invoices->count());
        $this->components->twoColumnDetail('Invoices synced', (string) $synced);
        $this->components->twoColumnDetail('Invoices failed', (string) $failed);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return Collection<int, Invoice>
     */
    private function invoices(): Collection
    {
        $invoiceIds = array_filter(array_map('intval', (array) $this->option('invoice')));

        return Invoice::query()
            ->with(['billable', 'holder'])
            ->where('operation_type', OperationType::RECEIVABLE->value)
            ->where('status', InvoiceStatus::PENDING->value)
            ->where(function ($query): void {
                $query->where('payment_method', PaymentMethod::BOLETO->value)
                    ->orWhere(function ($query): void {
                        $query->whereToday('due_date')
                            ->whereIn('payment_method', [
                                PaymentMethod::PIX->value,
                                PaymentMethod::CREDIT_CARD->value,
                            ]);
                    });
            })
            ->whereNotIn('id', GatewayPayment::query()
                ->select('invoice_id')
                ->whereNotNull('invoice_id'))
            ->when(
                $invoiceIds !== [],
                fn ($query) => $query->whereKey($invoiceIds),
            )
            ->orderBy('id')
            ->get();
    }
}

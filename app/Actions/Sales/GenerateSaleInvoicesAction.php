<?php

namespace App\Actions\Sales;

use App\Actions\BaseAction;
use App\Models\Sale;
use App\Services\Billing\InvoiceGenerator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;

class GenerateSaleInvoicesAction extends BaseAction
{
    /** Authorization is performed by the invoking controller. */
    protected string $ability = '';

    public function __construct(private readonly InvoiceGenerator $invoiceGenerator) {}

    protected function handle(mixed $input): mixed
    {
        if (! $input instanceof Sale) {
            throw new InvalidArgumentException('GenerateSaleInvoicesAction expects a Sale.');
        }

        $input->invoices()->where('status', '!=', 'paid')->delete();
        $invoices = $this->invoiceGenerator->generate($input);
        $this->queueGatewayInvoiceSync($invoices);

        return $invoices;
    }

    private function queueGatewayInvoiceSync(Collection $invoices): void
    {
        $invoicesToSync = $invoices->filter(fn ($invoice): bool => $invoice->shouldGenerateGatewayTransaction());

        if ($invoicesToSync->isNotEmpty()) {
            Artisan::queue('gateway:sync-invoices', ['--invoice' => $invoicesToSync->modelKeys()])->afterCommit();
        }
    }
}

<?php

namespace App\Actions\Sales;

use App\Actions\BaseAction;
use App\DTOs\Sales\CreateSaleDTO;
use App\Models\Sale;
use App\Services\BillableItemService;
use App\Services\Billing\InvoiceGenerator;
use App\Services\StockRecalculationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;

class CreateSaleAction extends BaseAction
{
    /** Authorization is performed by SaleController. */
    protected string $ability = '';

    public function __construct(
        private readonly BillableItemService $billableItemService,
        private readonly InvoiceGenerator $invoiceGenerator,
        private readonly StockRecalculationService $stockRecalculationService,
    ) {}

    protected function handle(mixed $input): mixed
    {
        if (! $input instanceof CreateSaleDTO) {
            throw new InvalidArgumentException('CreateSaleAction expects a CreateSaleDTO.');
        }

        $data = $input->data;
        $items = Arr::pull($data, 'items', []);
        $generateInvoices = (bool) Arr::pull($data, 'generate_invoices', true);
        $sale = Sale::query()->create($data);

        $this->billableItemService->syncSaleItems($sale, $items, (float) ($data['discount_value'] ?? 0));
        $sale = $sale->refresh();

        if ($generateInvoices) {
            $this->queueGatewayInvoiceSync($this->invoiceGenerator->generate($sale));
        }

        return $sale->load('items');
    }

    protected function dispatchEvents(mixed $result, mixed $input): void
    {
        if ($result instanceof Sale) {
            $this->stockRecalculationService->recalculateProducts(
                $result->items->pluck('product_id')->filter()->all(),
            );
        }
    }

    private function queueGatewayInvoiceSync(Collection $invoices): void
    {
        $invoicesToSync = $invoices->filter(fn ($invoice): bool => $invoice->shouldGenerateGatewayTransaction());

        if ($invoicesToSync->isNotEmpty()) {
            Artisan::queue('gateway:sync-invoices', ['--invoice' => $invoicesToSync->modelKeys()])->afterCommit();
        }
    }
}

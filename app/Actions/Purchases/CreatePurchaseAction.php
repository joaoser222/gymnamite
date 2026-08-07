<?php

namespace App\Actions\Purchases;

use App\Actions\BaseAction;
use App\DTOs\Purchases\CreatePurchaseDTO;
use App\Enums\BillableStatus;
use App\Models\Purchase;
use App\Services\BillableItemService;
use App\Services\Billing\InvoiceGenerator;
use App\Services\StockRecalculationService;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class CreatePurchaseAction extends BaseAction
{
    /** Authorization is performed by PurchaseController. */
    protected string $ability = '';

    public function __construct(
        private readonly BillableItemService $billableItemService,
        private readonly InvoiceGenerator $invoiceGenerator,
        private readonly StockRecalculationService $stockRecalculationService,
    ) {}

    protected function handle(mixed $input): mixed
    {
        if (! $input instanceof CreatePurchaseDTO) {
            throw new InvalidArgumentException('CreatePurchaseAction expects a CreatePurchaseDTO.');
        }

        $data = $input->data;
        $items = Arr::pull($data, 'items', []);
        $generateInvoices = (bool) Arr::pull($data, 'generate_invoices', true);

        if ($generateInvoices) {
            $data['status'] = BillableStatus::COMPLETED->value;
        }

        $purchase = Purchase::query()->create($data);
        $this->billableItemService->syncPurchaseItems($purchase, $items, (float) ($data['discount_value'] ?? 0));
        $purchase = $purchase->refresh();

        if ($generateInvoices) {
            $this->invoiceGenerator->generate($purchase);
        }

        return $purchase->load('items');
    }

    protected function dispatchEvents(mixed $result, mixed $input): void
    {
        if ($result instanceof Purchase) {
            $this->stockRecalculationService->recalculateProducts(
                $result->items->pluck('product_id')->filter()->all(),
            );
        }
    }
}

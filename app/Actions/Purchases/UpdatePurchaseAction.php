<?php

namespace App\Actions\Purchases;

use App\Actions\BaseAction;
use App\Actions\Exceptions\UpdateBillableBlockedException;
use App\DTOs\Purchases\UpdatePurchaseDTO;
use App\Enums\BillableStatus;
use App\Models\Purchase;
use App\Services\BillableItemService;
use App\Services\Billing\InvoiceGenerator;
use App\Services\StockRecalculationService;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class UpdatePurchaseAction extends BaseAction
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
        if (! $input instanceof UpdatePurchaseDTO) {
            throw new InvalidArgumentException('UpdatePurchaseAction expects an UpdatePurchaseDTO.');
        }

        $purchase = $input->purchase;

        if ($purchase->status !== BillableStatus::OPEN->value) {
            throw new UpdateBillableBlockedException('Somente compras pendentes podem ser atualizadas.');
        }

        $productIdsBeforeUpdate = $purchase->items()->pluck('product_id')->filter()->all();
        $data = $input->data;
        $items = Arr::pull($data, 'items', []);
        $generateInvoices = (bool) Arr::pull($data, 'generate_invoices', false);

        if ($generateInvoices) {
            $data['status'] = BillableStatus::COMPLETED->value;
        }

        $purchase->update($data);
        $this->billableItemService->syncPurchaseItems($purchase, $items, (float) ($data['discount_value'] ?? 0));
        $purchase = $purchase->refresh()->load('items');

        if ($generateInvoices && ! $purchase->invoices()->exists()) {
            $this->invoiceGenerator->generate($purchase);
        }

        $purchase->setAttribute('recalculation_product_ids', array_values(array_unique([
            ...$productIdsBeforeUpdate,
            ...$purchase->items->pluck('product_id')->filter()->all(),
        ])));

        return $purchase;
    }

    protected function dispatchEvents(mixed $result, mixed $input): void
    {
        if ($result instanceof Purchase) {
            $this->stockRecalculationService->recalculateProducts($result->getAttribute('recalculation_product_ids') ?? []);
        }
    }
}

<?php

namespace App\Actions\Sales;

use App\Actions\BaseAction;
use App\Actions\Exceptions\UpdateBillableBlockedException;
use App\DTOs\Sales\UpdateSaleDTO;
use App\Enums\BillableStatus;
use App\Models\Sale;
use App\Services\BillableItemService;
use App\Services\StockRecalculationService;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class UpdateSaleAction extends BaseAction
{
    /** Authorization is performed by SaleController. */
    protected string $ability = '';

    public function __construct(
        private readonly BillableItemService $billableItemService,
        private readonly GenerateSaleInvoicesAction $generateSaleInvoices,
        private readonly StockRecalculationService $stockRecalculationService,
    ) {}

    protected function handle(mixed $input): mixed
    {
        if (! $input instanceof UpdateSaleDTO) {
            throw new InvalidArgumentException('UpdateSaleAction expects an UpdateSaleDTO.');
        }

        $sale = $input->sale;

        if ($sale->status !== BillableStatus::OPEN->value) {
            throw new UpdateBillableBlockedException('Somente vendas pendentes podem ser atualizadas.');
        }

        if ($sale->invoices()->whereHas('gatewayPayment')->exists()) {
            throw new UpdateBillableBlockedException(
                'Vendas com faturas vinculadas a transações no gateway não podem ser atualizadas.',
            );
        }

        $productIdsBeforeUpdate = $sale->items()->pluck('product_id')->filter()->all();
        $data = $input->data;
        $items = Arr::pull($data, 'items', []);
        $generateInvoices = (bool) Arr::pull($data, 'generate_invoices', true);

        $sale->invoices()->delete();
        $sale->update($data);
        $this->billableItemService->syncSaleItems($sale, $items, (float) ($data['discount_value'] ?? 0));
        $sale = $sale->refresh()->load('items');

        if ($generateInvoices) {
            $this->generateSaleInvoices->execute($sale);
        }

        $sale->setAttribute('recalculation_product_ids', array_values(array_unique([
            ...$productIdsBeforeUpdate,
            ...$sale->items->pluck('product_id')->filter()->all(),
        ])));

        return $sale;
    }

    protected function dispatchEvents(mixed $result, mixed $input): void
    {
        if ($result instanceof Sale) {
            $this->stockRecalculationService->recalculateProducts($result->getAttribute('recalculation_product_ids') ?? []);
        }
    }
}

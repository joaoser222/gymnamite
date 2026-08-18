<?php

namespace App\Actions\Sales;

use App\Actions\BaseAction;
use App\DTOs\Sales\CreateSaleDTO;
use App\Models\Sale;
use App\Services\BillableItemService;
use App\Services\StockRecalculationService;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class CreateSaleAction extends BaseAction
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
            $this->generateSaleInvoices->execute($sale);
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
}

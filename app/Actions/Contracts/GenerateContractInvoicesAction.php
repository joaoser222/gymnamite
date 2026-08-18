<?php

namespace App\Actions\Contracts;

use App\Actions\BaseAction;
use App\DTOs\Contracts\ActionResultDTO;
use App\DTOs\Invoices\InvoiceResultDTO;
use App\Models\Contract;
use App\Models\Invoice;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Services\Billing\InvoiceGenerator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;

class GenerateContractInvoicesAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Contract::class;

    public function __construct(
        private readonly ContractRepositoryInterface $contractRepository,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly InvoiceGenerator $invoiceGenerator,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! is_int($input)) {
            throw new \InvalidArgumentException('GenerateContractInvoicesAction requires a contract ID.');
        }

        $contractId = $input;

        $contract = $this->contractRepository->findOrFail($contractId);

        // Delete existing unpaid invoices
        $this->invoiceRepository->newQuery()
            ->where('billable_id', $contract->id)
            ->where('billable_type', $contract->getMorphClass())
            ->where('status', '!=', 'paid')
            ->delete();

        $invoices = $this->invoiceGenerator->generate($contract);
        $discountTotal = round($invoices->sum('discount_value'), 4);

        $this->queueGatewayInvoiceSync($invoices);

        $this->contractRepository->update($contract, [
            'discount_value' => $discountTotal,
            'total' => round($contract->gross_value - $discountTotal, 4),
            'accepted_terms' => 'accepted',
        ]);

        $invoiceDtos = $invoices->map(fn (Invoice $invoice) => InvoiceResultDTO::fromModel($invoice))->all();

        return ActionResultDTO::success(
            $invoiceDtos,
            'Faturas geradas com sucesso.'
        );
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     */
    private function queueGatewayInvoiceSync(Collection $invoices): void
    {
        $invoicesToSync = $invoices->filter(
            fn (Invoice $invoice): bool => $invoice->shouldGenerateGatewayTransaction(),
        );

        if ($invoicesToSync->isEmpty()) {
            return;
        }

        Artisan::queue('gateway:sync-invoices', [
            '--invoice' => $invoicesToSync->modelKeys(),
        ])->afterCommit();
    }
}

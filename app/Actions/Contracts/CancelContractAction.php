<?php

namespace App\Actions\Contracts;

use App\Actions\BaseAction;
use App\DTOs\Contracts\ActionResultDTO;
use App\DTOs\Contracts\CancelContractDTO;
use App\Enums\BillableStatus;
use App\Enums\InvoiceStatus;
use App\Models\Contract;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Contracts\InvoiceRepositoryInterface;

class CancelContractAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Contract::class;

    public function __construct(
        private readonly ContractRepositoryInterface $contractRepository,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CancelContractDTO) {
            throw new \InvalidArgumentException('CancelContractAction requires a CancelContractDTO.');
        }

        $dto = $input;

        $contract = $this->contractRepository->findOrFail($dto->contract_id);

        $this->contractRepository->update($contract, [
            'status' => BillableStatus::CANCELED,
        ]);

        $this->invoiceRepository->newQuery()
            ->where('billable_id', $contract->id)
            ->where('billable_type', $contract->getMorphClass())
            ->where('status', '!=', InvoiceStatus::PAID->value)
            ->update(['status' => InvoiceStatus::CANCELED->value]);

        return ActionResultDTO::success(
            null,
            'Contrato cancelado com sucesso.'
        );
    }
}

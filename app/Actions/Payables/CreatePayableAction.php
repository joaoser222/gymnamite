<?php

namespace App\Actions\Payables;

use App\Actions\BaseAction;
use App\DTOs\Payables\ActionResultDTO;
use App\DTOs\Payables\CreatePayableDTO;
use App\Models\Payable;
use App\Repositories\Contracts\PayableRepositoryInterface;

class CreatePayableAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Payable::class;

    public function __construct(
        private readonly PayableRepositoryInterface $payableRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CreatePayableDTO) {
            throw new \InvalidArgumentException('CreatePayableAction requires a CreatePayableDTO.');
        }

        $dto = $input;
        $payable = $this->payableRepository->create([
            'supplier_id' => $dto->supplier_id,
            'due_date' => $dto->due_date,
            'total' => $dto->total,
            'payment_method' => $dto->payment_method,
            'operation_type' => $dto->operation_type,
            'annotations' => $dto->annotations,
            'financial_account_id' => $dto->financial_account_id,
            'financial_category_id' => $dto->financial_category_id,
        ]);

        return ActionResultDTO::success(
            $payable,
            'Conta a pagar criada com sucesso.'
        );
    }
}

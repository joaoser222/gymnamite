<?php

namespace App\Actions\FinancialAccounts;

use App\Actions\BaseAction;
use App\DTOs\FinancialAccounts\ActionResultDTO;
use App\DTOs\FinancialAccounts\UpdateFinancialAccountDTO;
use App\Models\FinancialAccount;
use App\Repositories\Contracts\FinancialAccountRepositoryInterface;

class UpdateFinancialAccountAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = FinancialAccount::class;

    public function __construct(
        private readonly FinancialAccountRepositoryInterface $financialAccountRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof UpdateFinancialAccountDTO) {
            throw new \InvalidArgumentException('UpdateFinancialAccountAction requires an UpdateFinancialAccountDTO.');
        }

        $dto = $input;
        $account = $this->financialAccountRepository->findOrFail($dto->id);

        $updateData = array_filter([
            'name' => $dto->name,
            'account_type' => $dto->account_type,
            'holder_name' => $dto->holder_name,
            'holder_document' => $dto->holder_document,
            'holder_birth_date' => $dto->holder_birth_date,
            'bank_account_number' => $dto->bank_account_number,
            'bank_agency' => $dto->bank_agency,
            'bank_account_type' => $dto->bank_account_type,
            'bank_code' => $dto->bank_code,
        ], fn ($value) => $value !== null);

        $this->financialAccountRepository->update($account, $updateData);

        return ActionResultDTO::success(
            $account->refresh(),
            'Conta financeira atualizada com sucesso.'
        );
    }
}

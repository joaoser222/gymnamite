<?php

namespace App\Actions\FinancialAccounts;

use App\Actions\BaseAction;
use App\DTOs\FinancialAccounts\ActionResultDTO;
use App\DTOs\FinancialAccounts\CreateFinancialAccountDTO;
use App\Models\FinancialAccount;
use App\Repositories\Contracts\FinancialAccountRepositoryInterface;

class CreateFinancialAccountAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = FinancialAccount::class;

    public function __construct(
        private readonly FinancialAccountRepositoryInterface $financialAccountRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CreateFinancialAccountDTO) {
            throw new \InvalidArgumentException('CreateFinancialAccountAction requires a CreateFinancialAccountDTO.');
        }

        $dto = $input;
        $account = $this->financialAccountRepository->create([
            'name' => $dto->name,
            'account_type' => $dto->account_type,
            'holder_name' => $dto->holder_name,
            'holder_document' => $dto->holder_document,
            'holder_birth_date' => $dto->holder_birth_date,
            'bank_account_number' => $dto->bank_account_number,
            'bank_agency' => $dto->bank_agency,
            'bank_account_type' => $dto->bank_account_type,
            'bank_code' => $dto->bank_code,
        ]);

        return ActionResultDTO::success(
            $account,
            'Conta financeira criada com sucesso.'
        );
    }
}

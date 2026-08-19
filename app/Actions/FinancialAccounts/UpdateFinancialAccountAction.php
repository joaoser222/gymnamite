<?php

namespace App\Actions\FinancialAccounts;

use App\Actions\BaseAction;
use App\DTOs\FinancialAccounts\ActionResultDTO;
use App\DTOs\FinancialAccounts\UpdateFinancialAccountDTO;
use App\Enums\FinancialAccountType;
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

        $effectiveType = $dto->account_type ?? $account->account_type;
        $effectiveValue = is_string($effectiveType) ? $effectiveType : $effectiveType->value;
        $isBank = $effectiveValue === FinancialAccountType::BANK->value;
        $isCash = $effectiveValue === FinancialAccountType::CASH->value;

        if ($isBank && $dto->account_type !== null) {
            if (! $dto->holder_name || ! $dto->holder_document || ! $dto->holder_birth_date || ! $dto->bank_account_number || ! $dto->bank_agency || ! $dto->bank_account_type || ! $dto->bank_code) {
                return ActionResultDTO::failure(
                    'Campos obrigatórios para conta bancária não preenchidos.',
                    [
                        'holder_name' => ['O campo nome do titular é obrigatório para contas bancárias.'],
                        'holder_document' => ['O campo documento do titular é obrigatório para contas bancárias.'],
                        'holder_birth_date' => ['O campo data de nascimento do titular é obrigatório para contas bancárias.'],
                        'bank_account_number' => ['O campo número da conta é obrigatório para contas bancárias.'],
                        'bank_agency' => ['O campo agência é obrigatório para contas bancárias.'],
                        'bank_account_type' => ['O campo tipo de conta bancária é obrigatório para contas bancárias.'],
                        'bank_code' => ['O campo código do banco é obrigatório para contas bancárias.'],
                    ]
                );
            }
        }

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

        if ($isCash) {
            $updateData = [
                ...$updateData,
                'holder_name' => null,
                'holder_document' => null,
                'holder_birth_date' => null,
                'bank_account_number' => null,
                'bank_agency' => null,
                'bank_account_type' => null,
                'bank_code' => null,
            ];
        }

        $this->financialAccountRepository->update($account, $updateData);

        return ActionResultDTO::success(
            $account->refresh(),
            'Conta financeira atualizada com sucesso.'
        );
    }
}

<?php

namespace App\Actions\FinancialAccounts;

use App\Actions\BaseAction;
use App\DTOs\FinancialAccounts\ActionResultDTO;
use App\DTOs\FinancialAccounts\CreateFinancialAccountDTO;
use App\Enums\FinancialAccountType;
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

        $isBank = $dto->account_type === FinancialAccountType::BANK->value;
        $isCash = $dto->account_type === FinancialAccountType::CASH->value;

        if ($isBank && (! $dto->holder_name || ! $dto->holder_document || ! $dto->holder_birth_date || ! $dto->bank_account_number || ! $dto->bank_agency || ! $dto->bank_account_type || ! $dto->bank_code)) {
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

        $data = [
            'name' => $dto->name,
            'account_type' => $dto->account_type,
            'holder_name' => $dto->holder_name,
            'holder_document' => $dto->holder_document,
            'holder_birth_date' => $dto->holder_birth_date,
            'bank_account_number' => $dto->bank_account_number,
            'bank_agency' => $dto->bank_agency,
            'bank_account_type' => $dto->bank_account_type,
            'bank_code' => $dto->bank_code,
        ];

        if ($isCash) {
            $data = [
                ...$data,
                'holder_name' => null,
                'holder_document' => null,
                'holder_birth_date' => null,
                'bank_account_number' => null,
                'bank_agency' => null,
                'bank_account_type' => null,
                'bank_code' => null,
            ];
        }

        $account = $this->financialAccountRepository->create($data);

        return ActionResultDTO::success(
            $account,
            'Conta financeira criada com sucesso.'
        );
    }
}

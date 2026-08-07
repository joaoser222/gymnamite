<?php

namespace App\Actions\Contracts;

use App\Actions\BaseAction;
use App\DTOs\Contracts\ActionResultDTO;
use App\DTOs\Contracts\ContractResultDTO;
use App\DTOs\Contracts\UpdateContractDTO;
use App\Models\Contract;
use App\Repositories\Contracts\ContractRepositoryInterface;

class UpdateContractAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Contract::class;

    public function __construct(
        private readonly ContractRepositoryInterface $contractRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof UpdateContractDTO) {
            throw new \InvalidArgumentException('UpdateContractAction requires an UpdateContractDTO.');
        }

        $dto = $input;

        $contract = $this->contractRepository->findOrFail($dto->id);

        if ($dto->annotations !== null) {
            $this->contractRepository->update($contract, ['annotations' => $dto->annotations]);
        }

        return ActionResultDTO::success(
            ContractResultDTO::fromModel($contract->refresh()),
            'Contrato atualizado com sucesso.'
        );
    }
}

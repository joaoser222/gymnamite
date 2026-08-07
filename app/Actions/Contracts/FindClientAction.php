<?php

namespace App\Actions\Contracts;

use App\Actions\BaseAction;
use App\DTOs\Clients\ActionResultDTO;
use App\DTOs\Clients\ClientResultDTO;
use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;

class FindClientAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Client::class;

    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! is_string($input)) {
            throw new \InvalidArgumentException('FindClientAction requires a document string.');
        }

        $document = $input;
        $email = null;

        $client = null;

        if (! empty($document)) {
            $client = $this->clientRepository->findByDocument($document);
        }

        if ($client === null && ! empty($email)) {
            $client = $this->clientRepository->findByEmail($email);
        }

        if ($client === null) {
            return ActionResultDTO::success(null, 'Cliente não encontrado.');
        }

        return ActionResultDTO::success(
            ClientResultDTO::fromModel($client),
            'Cliente encontrado.'
        );
    }
}

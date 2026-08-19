<?php

namespace App\Actions\Supplier;

use App\Actions\BaseAction;
use App\DTOs\Supplier\ActionResultDTO;
use App\DTOs\Supplier\CreateSupplierDTO;
use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;

class CreateSupplierAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Supplier::class;

    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CreateSupplierDTO) {
            throw new \InvalidArgumentException('CreateSupplierAction requires a CreateSupplierDTO.');
        }

        $dto = $input;
        $supplier = $this->supplierRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'document' => $dto->document,
            'phone' => $dto->phone,
            'address' => $dto->address,
            'address_number' => $dto->address_number,
            'address_complement' => $dto->address_complement,
            'address_state' => $dto->address_state,
            'address_city' => $dto->address_city,
            'address_district' => $dto->address_district,
            'address_postal_code' => $dto->address_postal_code,
        ]);

        return ActionResultDTO::success(
            $supplier,
            'Fornecedor criado com sucesso.'
        );
    }
}

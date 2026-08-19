<?php

namespace App\Actions\Supplier;

use App\Actions\BaseAction;
use App\DTOs\Supplier\ActionResultDTO;
use App\DTOs\Supplier\UpdateSupplierDTO;
use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;

class UpdateSupplierAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Supplier::class;

    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof UpdateSupplierDTO) {
            throw new \InvalidArgumentException('UpdateSupplierAction requires an UpdateSupplierDTO.');
        }

        $dto = $input;
        $supplier = $this->supplierRepository->findOrFail($dto->id);

        $updateData = array_filter([
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
        ], fn ($value) => $value !== null);

        $this->supplierRepository->update($supplier, $updateData);

        return ActionResultDTO::success(
            $supplier->refresh(),
            'Fornecedor atualizado com sucesso.'
        );
    }
}

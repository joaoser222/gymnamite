<?php

namespace App\Actions\Contracts;

use App\Actions\BaseAction;
use App\DTOs\Contracts\ActionResultDTO;
use App\DTOs\Contracts\ContractResultDTO;
use App\DTOs\Contracts\CreateContractDTO;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\PlanTier;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Services\Billing\DiscountCalculator;
use App\Services\Billing\InstallmentSplitter;
use App\Services\Billing\InvoiceGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;

class CreateContractAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Contract::class;

    public function __construct(
        private readonly ContractRepositoryInterface $contractRepository,
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly PlanRepositoryInterface $planRepository,
        private readonly CouponRepositoryInterface $couponRepository,
        private readonly InvoiceGenerator $invoiceGenerator,
        private readonly DiscountCalculator $discountCalculator,
        private readonly InstallmentSplitter $installmentSplitter,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CreateContractDTO) {
            throw new \InvalidArgumentException('CreateContractAction requires a CreateContractDTO.');
        }

        $dto = $input;

        $plan = $this->planRepository->newQuery()
            ->with(['tiers', 'planCategory'])
            ->where('visibility', 'visible')
            ->whereKey($dto->plan_id)
            ->firstOrFail();

        /** @var PlanTier|null $tier */
        $tier = $plan->tiers->firstWhere('quantity', $dto->installments);

        if ($tier === null) {
            return ActionResultDTO::failure(
                'A duração selecionada não está disponível para este plano.',
                ['installments' => 'A duração selecionada não está disponível para este plano.']
            );
        }

        $coupon = null;

        if (! empty($dto->coupon_code)) {
            $coupon = $this->couponRepository->newQuery()
                ->where('code', mb_strtoupper((string) $dto->coupon_code))
                ->where('visibility', 'visible')
                ->first();

            if ($coupon === null) {
                return ActionResultDTO::failure(
                    'O cupom informado não está disponível.',
                    ['coupon_code' => 'O cupom informado não está disponível.']
                );
            }

            if ($coupon->expiration_date !== null && $coupon->expiration_date->isBefore(CarbonImmutable::today())) {
                return ActionResultDTO::failure(
                    'O cupom informado está expirado.',
                    ['coupon_code' => 'O cupom informado está expirado.']
                );
            }
        }

        $grossValue = round((float) $tier->price * (int) $dto->installments, 4);

        $clientData = [
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'document' => $dto->document,
            'gender' => $dto->gender,
            'birth_date' => $dto->birth_date,
            'legal_representative' => $dto->legal_representative,
            'legal_representative_name' => $dto->legal_representative_name,
            'legal_representative_document' => $dto->legal_representative_document,
            'legal_representative_birth_date' => $dto->legal_representative_birth_date,
            'address_postal_code' => $dto->address_postal_code,
            'address' => $dto->address,
            'address_number' => $dto->address_number,
            'address_complement' => $dto->address_complement,
            'address_district' => $dto->address_district,
            'address_state' => $dto->address_state,
            'address_city' => $dto->address_city,
        ];

        $client = $dto->client_id
            ? $this->clientRepository->findOrFail($dto->client_id)
            : new Client;

        if ($client->exists) {
            $client->update($clientData);
        } else {
            $client = $this->clientRepository->create($clientData);
        }

        $contract = $this->contractRepository->create([
            'plan_name' => $plan->name,
            'modality_quantity' => (string) $plan->modality_quantity,
            'gross_value' => $grossValue,
            'discount_value' => 0,
            'total' => $grossValue,
            'payment_method' => 'cash',
            'first_due_date' => CarbonImmutable::today()->format('Y-m-d'),
            'installments' => $dto->installments,
            'accepted_terms' => $dto->generate_invoices ? 'accepted' : 'pending',
            'annotations' => $dto->annotations,
            'coupon_id' => $coupon?->id,
            'plan_id' => $plan->id,
            'client_id' => $client->id,
            'visibility' => 'visible',
        ]);

        if ($dto->generate_invoices) {
            $invoices = $this->invoiceGenerator->generate($contract);
            $discountTotal = round($invoices->sum('discount_value'), 4);

            $this->queueGatewayInvoiceSync($invoices);

            $this->contractRepository->update($contract, [
                'discount_value' => $discountTotal,
                'total' => round($grossValue - $discountTotal, 4),
            ]);
        } else {
            $grossInstallments = $this->installmentSplitter->split($grossValue, $dto->installments);
            $discountTotal = round(array_sum($this->discountCalculator->calculate(
                $contract->loadMissing('coupon'),
                $grossInstallments,
            )), 4);

            $this->contractRepository->update($contract, [
                'discount_value' => $discountTotal,
                'total' => round($grossValue - $discountTotal, 4),
            ]);
        }

        return ActionResultDTO::success(
            ContractResultDTO::fromModel($contract->refresh()),
            'Contrato criado com sucesso.'
        );
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     */
    private function queueGatewayInvoiceSync(Collection $invoices): void
    {
        $invoicesToSync = $invoices->filter(
            fn ($invoice): bool => $invoice->shouldGenerateGatewayTransaction(),
        );

        if ($invoicesToSync->isEmpty()) {
            return;
        }

        Artisan::queue('gateway:sync-invoices', [
            '--invoice' => $invoicesToSync->modelKeys(),
        ])->afterCommit();
    }
}

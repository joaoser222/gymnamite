<?php

namespace App\Services\Gateway;

use App\Enums\Gateway\InvoiceStatus;
use App\Models\GatewayAccount;
use App\Models\GatewayInvoice;
use App\PaymentGateways\PaymentGatewayManager;
use App\Repositories\Contracts\GatewayAccountRepositoryInterface;
use App\Repositories\Contracts\GatewayInvoiceRepositoryInterface;
use Illuminate\Support\Collection;

class FiscalSyncOrchestrator
{
    /**
     * Statuses in which the invoice still exists at the provider and its local
     * record should not be overwritten with an artificial error when the
     * provider answers 404 (the invoice was probably removed there).
     */
    private const TERMINAL_NOT_FOUND_STATUSES = [
        InvoiceStatus::CANCELED,
        InvoiceStatus::ERROR,
        InvoiceStatus::UNKNOWN,
    ];

    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly GatewayAdapterResolver $adapterResolver,
        private readonly GatewayAccountRepositoryInterface $accountRepository,
        private readonly GatewayInvoiceRepositoryInterface $invoiceRepository,
    ) {}

    /**
     * Reconcile fiscal invoices already issued at provider with local state.
     *
     * @param  array<int, int>  $accountIds  Filter by gateway accounts (empty = all eligible).
     * @param  array<int, string>  $statuses  Filter by invoice status (empty = all).
     * @return array<int, array<string, int>>
     */
    public function syncAll(array $accountIds = [], array $statuses = [], bool $force = false): array
    {
        $results = [];

        foreach ($this->accounts($accountIds) as $account) {
            $results[$account->id] = $this->syncAccount($account, $statuses, $force);
        }

        return $results;
    }

    /**
     * @param  array<int, string>  $statuses
     * @return array<string, int>
     */
    private function syncAccount(GatewayAccount $account, array $statuses, bool $force): array
    {
        $result = ['found' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0];

        try {
            $adapter = $this->adapterResolver->invoicingAdapter($account);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->withAccountFailure($result);
        }

        $this->invoices($account, $statuses)->each(function (GatewayInvoice $invoice) use ($adapter, $force, &$result): void {
            $result['found']++;

            try {
                $previousStatus = $invoice->status;
                $synced = $adapter->syncInvoice($invoice, $force);

                if ($synced === null) {
                    $this->markNotFound($invoice);
                    $result['updated']++;

                    return;
                }

                $result[$synced->status === $previousStatus ? 'unchanged' : 'updated']++;
            } catch (\Throwable $exception) {
                report($exception);
                $result['failed']++;
            }
        });

        return $result;
    }

    /**
     * @param  array<int, int>  $accountIds
     * @return Collection<int, GatewayAccount>
     */
    private function accounts(array $accountIds): Collection
    {
        return $this->accountRepository->newQuery()
            ->invoicingEligible()
            ->when($accountIds !== [], fn ($query) => $query->whereKey($accountIds))
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<int, string>  $statuses
     * @return Collection<int, GatewayInvoice>
     */
    private function invoices(GatewayAccount $account, array $statuses): Collection
    {
        return $this->invoiceRepository->newQuery()
            ->where('gateway_account_id', $account->id)
            ->whereNotNull('gateway_reference_key')
            ->when(
                $statuses !== [],
                fn ($query) => $query->whereIn('status', $statuses),
            )
            ->orderBy('id')
            ->get();
    }

    private function markNotFound(GatewayInvoice $invoice): void
    {
        if (in_array($invoice->status, self::TERMINAL_NOT_FOUND_STATUSES, true)) {
            return;
        }

        $this->invoiceRepository->update($invoice, [
            'status' => InvoiceStatus::ERROR->value,
            'status_description' => 'Nota não encontrada no provedor',
            'error_message' => 'not found on provider',
        ]);
    }

    /**
     * @param  array<string, int>  $result
     * @return array<string, int>
     */
    private function withAccountFailure(array $result): array
    {
        return [
            ...$result,
            'failed' => $result['failed'] + 1,
        ];
    }
}

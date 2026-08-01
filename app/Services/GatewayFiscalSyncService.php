<?php

namespace App\Services;

use App\Enums\Gateway\InvoiceStatus;
use App\Models\GatewayAccount;
use App\Models\GatewayInvoice;
use App\PaymentGateways\Contracts\PaymentGatewayInvoicingAdapter;
use App\PaymentGateways\PaymentGatewayManager;
use Illuminate\Support\Collection;

class GatewayFiscalSyncService
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

    public function __construct(private readonly PaymentGatewayManager $gatewayManager) {}

    /**
     * Reconcilia as notas fiscais já emitidas no provedor com o estado local.
     *
     * @param  array<int, int>  $accountIds  Filtra por contas gateway (vazio = todas as elegíveis).
     * @param  array<int, string>  $statuses  Filtra por status de nota (vazio = todos).
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
     * @param  array<int, int>  $accountIds
     * @return Collection<int, GatewayAccount>
     */
    private function accounts(array $accountIds): Collection
    {
        return GatewayAccount::query()
            ->where('invoicing_supported', true)
            ->where('invoicing_configured', true)
            ->where('invoicing_enabled', true)
            ->when($accountIds !== [], fn ($query) => $query->whereKey($accountIds))
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<int, string>  $statuses
     * @return array<string, int>
     */
    private function syncAccount(GatewayAccount $account, array $statuses, bool $force): array
    {
        $result = ['found' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0];

        try {
            $adapter = $this->invoicingAdapter($account);
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
     * @param  array<int, string>  $statuses
     * @return \Illuminate\Database\Eloquent\Collection<int, GatewayInvoice>
     */
    private function invoices(GatewayAccount $account, array $statuses): \Illuminate\Database\Eloquent\Collection
    {
        return GatewayInvoice::query()
            ->where('gateway_account_id', $account->id)
            ->whereNotNull('gateway_reference_key')
            ->when(
                $statuses !== [],
                fn ($query) => $query->whereIn('status', $statuses),
            )
            ->orderBy('id')
            ->get();
    }

    private function invoicingAdapter(GatewayAccount $account): PaymentGatewayInvoicingAdapter
    {
        return $this->gatewayManager->invoicingAdapter($account);
    }

    private function markNotFound(GatewayInvoice $invoice): void
    {
        if (in_array($invoice->status, self::TERMINAL_NOT_FOUND_STATUSES, true)) {
            return;
        }

        $invoice->update([
            'status' => InvoiceStatus::ERROR,
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

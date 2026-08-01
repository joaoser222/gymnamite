<?php

namespace App\Services;

use App\Enums\Gateway\InvoiceStatus;
use App\Models\GatewayAccount;
use App\Models\GatewayInvoice;
use App\Models\GatewayPayment;
use App\Models\Invoice;
use App\PaymentGateways\Definitions\PaymentGatewayDefinition;
use App\PaymentGateways\PaymentGatewayManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GatewayInvoicingService
{
    public function __construct(private readonly PaymentGatewayManager $gatewayManager) {}

    public function request(Invoice $invoice): GatewayInvoice
    {
        [$gatewayInvoice, $shouldRequest] = DB::transaction(function () use ($invoice): array {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $payment = $this->latestPayment($lockedInvoice)->lockForUpdate()->first();

            if ($payment === null) {
                throw new RuntimeException('Este recebimento não possui pagamento do gateway.');
            }

            $account = $payment->gatewayAccount;
            $definition = $this->gatewayManager->find($account->name);

            if (! $this->isEligible($account, $definition)) {
                throw new RuntimeException('A conta do gateway não está habilitada para emissão fiscal.');
            }

            $existing = GatewayInvoice::query()
                ->where('invoice_id', $lockedInvoice->id)
                ->where('gateway_payment_id', $payment->id)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if (in_array($existing->status, [
                    InvoiceStatus::PROCESSING,
                    InvoiceStatus::SCHEDULED,
                    InvoiceStatus::AUTHORIZED,
                    InvoiceStatus::SYNCHRONIZED,
                ], true)) {
                    return [$existing, false];
                }

                if ($existing->status !== InvoiceStatus::ERROR) {
                    throw new RuntimeException('Já existe uma nota fiscal para este recebimento.');
                }

                $existing->update(['status' => InvoiceStatus::PROCESSING]);

                return [$existing, true];
            }

            return [GatewayInvoice::create([
                'status' => InvoiceStatus::PROCESSING,
                'value' => $payment->gross_value,
                'gateway_account_id' => $payment->gateway_account_id,
                'gateway_payment_id' => $payment->id,
                'invoice_id' => $lockedInvoice->id,
            ]), true];
        });

        if (! $shouldRequest || $gatewayInvoice->gateway_reference_key !== null) {
            return $gatewayInvoice;
        }

        $payment = $gatewayInvoice->gatewayPayment()->with('gatewayAccount')->firstOrFail();
        $account = $payment->gatewayAccount;
        $configuration = data_get($account->settings, 'invoicing', []);

        try {
            return $this->gatewayManager
                ->invoicingAdapter($account)
                ->requestInvoice($payment, $configuration, $gatewayInvoice);
        } catch (\Throwable $exception) {
            $gatewayInvoice->update([
                'status' => InvoiceStatus::ERROR,
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function eligibilityQuery(Builder $query): Builder
    {
        return $query
            ->addSelect([
                'can_request_gateway_invoice' => GatewayPayment::query()
                    ->selectRaw('COALESCE(CASE WHEN gateway_accounts.invoicing_supported = 1 AND gateway_accounts.invoicing_configured = 1 AND gateway_accounts.invoicing_enabled = 1 THEN 1 ELSE 0 END, 0)')
                    ->join('gateway_accounts', 'gateway_accounts.id', '=', 'gateway_payments.gateway_account_id')
                    ->whereColumn('gateway_payments.invoice_id', 'invoices.id')
                    ->whereNotExists(function ($query): void {
                        $query->selectRaw('1')
                            ->from('gateway_invoices')
                            ->whereColumn('gateway_invoices.invoice_id', 'invoices.id')
                            ->whereColumn('gateway_invoices.gateway_payment_id', 'gateway_payments.id')
                            ->whereNotIn('gateway_invoices.status', [
                                InvoiceStatus::ERROR->value,
                            ]);
                    })
                    ->orderByDesc('gateway_payments.created_at')
                    ->orderByDesc('gateway_payments.id')
                    ->limit(1),
            ])
            ->addSelect([
                'gateway_invoice_request_reason' => GatewayPayment::query()
                    ->selectRaw("COALESCE(CASE WHEN gateway_accounts.name IS NULL THEN 'no_gateway_payment' WHEN gateway_accounts.invoicing_supported = 0 THEN 'provider_not_supported' WHEN gateway_accounts.invoicing_enabled = 0 THEN 'account_not_enabled' WHEN gateway_accounts.invoicing_configured = 0 THEN 'invoicing_not_configured' WHEN EXISTS (SELECT 1 FROM gateway_invoices WHERE gateway_invoices.invoice_id = invoices.id AND gateway_invoices.gateway_payment_id = gateway_payments.id AND gateway_invoices.status <> 'error') THEN 'already_requested' ELSE 'eligible' END, 'no_gateway_payment')")
                    ->join('gateway_accounts', 'gateway_accounts.id', '=', 'gateway_payments.gateway_account_id')
                    ->whereColumn('gateway_payments.invoice_id', 'invoices.id')
                    ->orderByDesc('gateway_payments.created_at')
                    ->orderByDesc('gateway_payments.id')
                    ->limit(1),
            ]);
    }

    private function latestPayment(Invoice $invoice): Builder
    {
        return GatewayPayment::query()
            ->where('invoice_id', $invoice->id)
            ->with('gatewayAccount')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    private function isEligible(GatewayAccount $account, ?PaymentGatewayDefinition $definition): bool
    {
        return $account->invoicing_enabled === true
            && $definition?->supportsInvoicing() === true
            && $account->invoicing_configured === true;
    }
}

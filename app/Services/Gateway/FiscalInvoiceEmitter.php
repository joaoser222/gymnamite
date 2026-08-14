<?php

namespace App\Services\Gateway;

use App\Enums\Gateway\InvoiceStatus;
use App\Models\GatewayInvoice;
use App\Models\GatewayPayment;
use App\Models\Invoice;
use App\PaymentGateways\PaymentGatewayManager;
use App\Repositories\Contracts\GatewayInvoiceRepositoryInterface;
use App\Repositories\Contracts\GatewayPaymentRepositoryInterface;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FiscalInvoiceEmitter
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly GatewayAdapterResolver $adapterResolver,
        private readonly GatewayInvoiceRepositoryInterface $gatewayInvoiceRepository,
        private readonly GatewayPaymentRepositoryInterface $gatewayPaymentRepository,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function emit(Invoice $invoice): GatewayInvoice
    {
        [$gatewayInvoice, $shouldRequest] = DB::transaction(function () use ($invoice): array {
            $lockedInvoice = $this->invoiceRepository->findOrFail($invoice->id);
            $payment = $this->latestPayment($lockedInvoice);

            if ($payment === null) {
                throw new RuntimeException('Este recebimento não possui pagamento do gateway.');
            }

            $account = $payment->gatewayAccount;

            if (! $this->adapterResolver->isInvoicingEligible($account)) {
                throw new RuntimeException('A conta do gateway não está habilitada para emissão fiscal.');
            }

            $existing = $this->gatewayInvoiceRepository->firstWhere([
                'invoice_id' => $lockedInvoice->id,
                'gateway_payment_id' => $payment->id,
            ]);

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

                $existing = $this->gatewayInvoiceRepository->update($existing, [
                    'status' => InvoiceStatus::PROCESSING->value,
                ]);

                return [$existing, true];
            }

            $gatewayInvoice = $this->gatewayInvoiceRepository->create([
                'status' => InvoiceStatus::PROCESSING->value,
                'value' => $payment->gross_value,
                'gateway_account_id' => $payment->gateway_account_id,
                'gateway_payment_id' => $payment->id,
                'invoice_id' => $lockedInvoice->id,
            ]);

            return [$gatewayInvoice, true];
        });

        if (! $shouldRequest || $gatewayInvoice->gateway_reference_key !== null) {
            return $gatewayInvoice;
        }

        $payment = $gatewayInvoice->gatewayPayment()->with('gatewayAccount')->firstOrFail();
        $account = $payment->gatewayAccount;
        $configuration = data_get($account->settings, 'invoicing', []);

        try {
            return $this->adapterResolver->invoicingAdapter($account)
                ->requestInvoice($payment, $configuration, $gatewayInvoice);
        } catch (\Throwable $exception) {
            $this->gatewayInvoiceRepository->update($gatewayInvoice, [
                'status' => InvoiceStatus::ERROR->value,
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
                    ->selectRaw('COALESCE(CASE WHEN gateway_accounts.invoicing_supported = true AND gateway_accounts.invoicing_configured = true AND gateway_accounts.invoicing_enabled = true THEN 1 ELSE 0 END, 0)')
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
                    ->selectRaw("COALESCE(CASE WHEN gateway_accounts.name IS NULL THEN 'no_gateway_payment' WHEN gateway_accounts.invoicing_supported = false THEN 'provider_not_supported' WHEN gateway_accounts.invoicing_enabled = false THEN 'account_not_enabled' WHEN gateway_accounts.invoicing_configured = false THEN 'invoicing_not_configured' WHEN EXISTS (SELECT 1 FROM gateway_invoices WHERE gateway_invoices.invoice_id = invoices.id AND gateway_invoices.gateway_payment_id = gateway_payments.id AND gateway_invoices.status <> 'error') THEN 'already_requested' ELSE 'eligible' END, 'no_gateway_payment')")
                    ->join('gateway_accounts', 'gateway_accounts.id', '=', 'gateway_payments.gateway_account_id')
                    ->whereColumn('gateway_payments.invoice_id', 'invoices.id')
                    ->orderByDesc('gateway_payments.created_at')
                    ->orderByDesc('gateway_payments.id')
                    ->limit(1),
            ]);
    }

    private function latestPayment(Invoice $invoice): ?GatewayPayment
    {
        return $this->gatewayPaymentRepository->newQuery()
            ->where('invoice_id', $invoice->id)
            ->with('gatewayAccount')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }
}

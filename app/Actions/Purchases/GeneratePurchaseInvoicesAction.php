<?php

namespace App\Actions\Purchases;

use App\Actions\BaseAction;
use App\Models\Purchase;
use App\Services\Billing\InvoiceGenerator;
use InvalidArgumentException;

class GeneratePurchaseInvoicesAction extends BaseAction
{
    /** Authorization is performed by the invoking controller. */
    protected string $ability = '';

    public function __construct(private readonly InvoiceGenerator $invoiceGenerator) {}

    protected function handle(mixed $input): mixed
    {
        if (! $input instanceof Purchase) {
            throw new InvalidArgumentException('GeneratePurchaseInvoicesAction expects a Purchase.');
        }

        $input->invoices()->where('status', '!=', 'paid')->delete();

        return $this->invoiceGenerator->generate($input);
    }
}

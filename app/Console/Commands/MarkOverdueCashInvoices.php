<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;

#[Signature('invoices:mark-overdue-cash')]
#[Description('Mark overdue pending cash invoices as overdued')]
class MarkOverdueCashInvoices extends Command
{
    public function handle(): int
    {
        $updated = Invoice::query()
            ->where('status', InvoiceStatus::PENDING->value)
            ->where('payment_method', PaymentMethod::CASH->value)
            ->whereDate('due_date', '<', Date::today())
            ->update(['status' => InvoiceStatus::OVERDUED->value]);

        $this->components->info("{$updated} cash invoices marked as overdued.");

        return self::SUCCESS;
    }
}

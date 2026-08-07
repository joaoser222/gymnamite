<?php

namespace App\Services\Billing;

use App\Contracts\BillingInvoiceSource;
use App\Models\Contract;
use App\Models\DirectLesson;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Model;

class BillingSourceResolver
{
    /**
     * Get relations to eager load for a given billing source.
     *
     * @return array<int, string>
     */
    public function sourceRelations(Model $source): array
    {
        return match ($source::class) {
            Contract::class => ['client', 'coupon'],
            DirectLesson::class => ['client'],
            Sale::class => ['client'],
            Purchase::class => ['supplier'],
            default => [],
        };
    }

    /**
     * Check if a model implements BillingInvoiceSource.
     */
    public function isBillingSource(Model $model): bool
    {
        return $model instanceof BillingInvoiceSource;
    }
}

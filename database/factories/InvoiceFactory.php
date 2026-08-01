<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OperationType;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'operation_type' => OperationType::INCOME,
            'invoice_type' => InvoiceType::RECEIVABLE,
            'due_date' => now()->addMonth(),
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'total' => 100,
            'paid_value' => 0,
            'status' => InvoiceStatus::PENDING,
        ];
    }
}

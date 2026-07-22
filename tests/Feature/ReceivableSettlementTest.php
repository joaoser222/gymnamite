<?php

namespace Tests\Feature;

use App\Enums\BillableStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\Permission;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceivableSettlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_receivable_as_paid_and_creates_a_movement(): void
    {
        $user = User::factory()->create();
        $permission = Permission::query()->create([
            'name' => 'receivables.mark_paid',
            'description' => 'Realizar baixa de recebimentos',
        ]);
        $user->permissions()->attach($permission);

        $client = Client::factory()->create();
        $sale = Sale::query()->create([
            'total' => 150,
            'gross_value' => 150,
            'discount_value' => 0,
            'status' => BillableStatus::OPEN,
            'payment_method' => PaymentMethod::CASH,
            'client_id' => $client->id,
        ]);

        $receivable = Receivable::query()->create([
            'invoice_type' => 'standard',
            'due_date' => '2026-07-20',
            'payment_method' => PaymentMethod::CASH,
            'gross_value' => 150,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => InvoiceStatus::PENDING,
            'visibility' => 'visible',
            'holder_id' => $client->id,
            'holder_type' => 'client',
            'billable_id' => $sale->id,
            'billable_type' => 'sale',
        ]);

        $response = $this->actingAs($user)->patch(route('receivables.mark-paid', $receivable), [
            'payment_date' => '2026-07-21',
        ]);

        $response->assertRedirect(route('receivables.index'));

        $this->assertDatabaseHas('invoices', [
            'id' => $receivable->id,
            'payment_date' => '2026-07-21',
            'paid_value' => 150,
            'status' => InvoiceStatus::PAID->value,
        ]);

        $this->assertDatabaseHas('movements', [
            'operation_type' => OperationType::RECEIVABLE->value,
            'movement_type' => MovementType::INTERNAL->value,
            'value' => 150,
            'invoice_id' => $receivable->id,
            'visibility' => 'visible',
        ]);
    }
}

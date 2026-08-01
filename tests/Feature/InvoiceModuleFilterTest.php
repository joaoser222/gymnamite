<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InvoiceModuleFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function grantPermission(User $user, string $permission): void
    {
        $permission = Permission::query()->create([
            'name' => $permission,
            'description' => $permission,
        ]);

        $user->permissions()->attach($permission);
    }

    public function test_payables_index_only_shows_payable_invoices(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'payables.view');

        Invoice::query()->create([
            'operation_type' => 'payable',
            'invoice_type' => 'standard',
            'due_date' => '2026-07-10',
            'payment_method' => 'cash',
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => 'pending',
            'visibility' => 'visible',
            'holder_type' => 'client',
            'holder_id' => 1,
            'billable_type' => 'sale',
            'billable_id' => 1,
        ]);

        Invoice::query()->create([
            'operation_type' => 'receivable',
            'invoice_type' => 'standard',
            'due_date' => '2026-07-11',
            'payment_method' => 'cash',
            'gross_value' => 200,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => 'pending',
            'visibility' => 'visible',
            'holder_type' => 'client',
            'holder_id' => 1,
            'billable_type' => 'sale',
            'billable_id' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('payables.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('payables/Index')
            ->has('payables.data', 1)
            ->where('payables.data.0.due_date', '2026-07-10')
            ->where('payables.data.0.status', 'pending')
        );
    }

    public function test_receivables_index_only_shows_receivable_invoices(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'receivables.view');

        Invoice::query()->create([
            'operation_type' => 'payable',
            'invoice_type' => 'standard',
            'due_date' => '2026-07-10',
            'payment_method' => 'cash',
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => 'pending',
            'visibility' => 'visible',
            'holder_type' => 'client',
            'holder_id' => 1,
            'billable_type' => 'sale',
            'billable_id' => 1,
        ]);

        Invoice::query()->create([
            'operation_type' => 'receivable',
            'invoice_type' => 'standard',
            'due_date' => '2026-07-11',
            'payment_method' => 'cash',
            'gross_value' => 200,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => 'pending',
            'visibility' => 'visible',
            'holder_type' => 'client',
            'holder_id' => 1,
            'billable_type' => 'sale',
            'billable_id' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('receivables.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('receivables/Index')
            ->has('receivables.data', 1)
            ->where('receivables.data.0.due_date', '2026-07-11')
            ->where('receivables.data.0.status', 'pending')
        );
    }

    public function test_payables_index_can_search_by_status(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'payables.view');

        Invoice::query()->create([
            'operation_type' => 'payable',
            'invoice_type' => 'standard',
            'due_date' => '2026-07-10',
            'payment_date' => '2026-07-11',
            'payment_method' => 'cash',
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 100,
            'installment_number' => 1,
            'status' => 'paid',
            'visibility' => 'visible',
            'holder_type' => 'supplier',
            'holder_id' => 1,
            'billable_type' => 'purchase',
            'billable_id' => 1,
        ]);

        Invoice::query()->create([
            'operation_type' => 'payable',
            'invoice_type' => 'standard',
            'due_date' => '2026-07-12',
            'payment_method' => 'cash',
            'gross_value' => 200,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => 'pending',
            'visibility' => 'visible',
            'holder_type' => 'supplier',
            'holder_id' => 1,
            'billable_type' => 'purchase',
            'billable_id' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('payables.index', [
            'searchField' => 'status',
            'search' => 'paid',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('payables/Index')
            ->has('payables.data', 1)
            ->where('payables.data.0.status', 'paid')
        );
    }

    public function test_receivables_index_can_search_by_payment_date(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'receivables.view');

        Invoice::query()->create([
            'operation_type' => 'receivable',
            'invoice_type' => 'standard',
            'due_date' => '2026-07-10',
            'payment_date' => '2026-07-15',
            'payment_method' => 'cash',
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 100,
            'installment_number' => 1,
            'status' => 'paid',
            'visibility' => 'visible',
            'holder_type' => 'client',
            'holder_id' => 1,
            'billable_type' => 'sale',
            'billable_id' => 1,
        ]);

        Invoice::query()->create([
            'operation_type' => 'receivable',
            'invoice_type' => 'standard',
            'due_date' => '2026-07-12',
            'payment_date' => '2026-07-20',
            'payment_method' => 'cash',
            'gross_value' => 200,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 200,
            'installment_number' => 1,
            'status' => 'paid',
            'visibility' => 'visible',
            'holder_type' => 'client',
            'holder_id' => 1,
            'billable_type' => 'sale',
            'billable_id' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('receivables.index', [
            'searchField' => 'payment_date',
            'search' => '2026-07-15',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('receivables/Index')
            ->has('receivables.data', 1)
            ->where('receivables.data.0.payment_date', '2026-07-15')
        );
    }
}

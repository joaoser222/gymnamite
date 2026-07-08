<?php

namespace Tests\Feature;

use App\Enums\GenderType;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\PlanTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HiringFlowTest extends TestCase
{
    use RefreshDatabase;

    private function grantPermission(User $user, string $permission): void
    {
        $permission = Permission::query()->create([
            'name' => $permission,
            'description' => $permission,
        ]);

        $user->permissions()->attach($permission);
    }

    private function grantHiringPermissions(User $user): void
    {
        $this->grantPermission($user, 'clients.create');
        $this->grantPermission($user, 'contracts.create');
    }

    private function createPlan(): Plan
    {
        $planCategory = PlanCategory::query()->create([
            'name' => 'Premium',
            'visibility' => 'visible',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plano Performance',
            'plan_category_id' => $planCategory->id,
            'modality_quantity' => 3,
            'description' => 'Plano com multiplas duracoes.',
            'visibility' => 'visible',
        ]);

        PlanTier::query()->create([
            'plan_id' => $plan->id,
            'quantity' => 3,
            'price' => 199.9,
        ]);

        PlanTier::query()->create([
            'plan_id' => $plan->id,
            'quantity' => 12,
            'price' => 699.9,
        ]);

        return $plan;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Plan $plan): array
    {
        return [
            'name' => 'Cliente Wizard',
            'email' => 'wizard@example.com',
            'phone' => '11999999999',
            'document' => '12345678901',
            'gender' => GenderType::MALE->value,
            'birth_date' => '1990-01-01',
            'legal_representative' => false,
            'address_postal_code' => '01001000',
            'address' => 'Rua Teste',
            'address_number' => '100',
            'address_complement' => 'Sala 1',
            'address_district' => 'Centro',
            'address_state' => 'SP',
            'address_city' => 'Sao Paulo',
            'coupon_code' => '',
            'plan_id' => $plan->id,
            'installments' => 12,
            'annotations' => 'Contratacao criada pelo wizard.',
            'accepted_terms' => true,
        ];
    }

    public function test_users_with_required_permissions_can_open_the_contract_wizard_page(): void
    {
        $user = User::factory()->create();
        $this->grantHiringPermissions($user);
        $plan = $this->createPlan();

        $response = $this->actingAs($user)->get(route('contracts.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('contracts/Create')
            ->where('routes.store', route('contracts.store'))
            ->where('options.plans.0.value', $plan->id)
            ->where('options.plans.0.title', 'Plano Performance')
        );
    }

    public function test_contract_wizard_creates_client_and_contract_in_a_single_submission(): void
    {
        $user = User::factory()->create();
        $this->grantHiringPermissions($user);
        $plan = $this->createPlan();
        $coupon = Coupon::query()->create([
            'code' => 'BEMVINDO',
            'percent' => 10,
            'discount_limit' => 100,
            'duration' => 30,
            'expiration_date' => '2026-12-31',
            'visibility' => 'visible',
        ]);

        $payload = $this->validPayload($plan);
        $payload['coupon_code'] = $coupon->code;

        $response = $this->actingAs($user)->post(route('contracts.store'), $payload);

        $response->assertRedirect(route('contracts.index'));

        $client = Client::query()->firstOrFail();
        $contract = Contract::query()->firstOrFail();

        $this->assertSame('Cliente Wizard', $client->name);
        $this->assertSame('wizard@example.com', $client->email);
        $this->assertSame($client->id, $contract->client_id);
        $this->assertSame($plan->id, $contract->plan_id);
        $this->assertSame('Plano Performance', $contract->plan_name);
        $this->assertSame('3', $contract->modality_quantity);
        $this->assertSame($coupon->id, $contract->coupon_id);
        $this->assertSame(12, $contract->installments);
        $this->assertSame(Date::today()->format('Y-m-d'), $contract->first_due_date?->format('Y-m-d'));
        $this->assertSame('accepted', $contract->accepted_terms);
        $this->assertEquals(699.9, $contract->gross_value);
        $this->assertEquals(69.99, $contract->discount_value);
        $this->assertEquals(629.91, $contract->total);
        $this->assertDatabaseCount('invoices', 12);

        $firstInvoice = Invoice::query()->orderBy('installment_number')->firstOrFail();

        $this->assertSame('contract', $firstInvoice->billable_type);
        $this->assertSame($contract->id, $firstInvoice->billable_id);
        $this->assertSame(Date::today()->format('Y-m-d'), $firstInvoice->due_date?->format('Y-m-d'));
        $this->assertEquals(5.8325, $firstInvoice->discount_value);
    }

    public function test_contract_wizard_updates_existing_client_when_document_is_found(): void
    {
        $user = User::factory()->create();
        $this->grantHiringPermissions($user);
        $plan = $this->createPlan();

        $client = Client::query()->create([
            'name' => 'Cliente Antigo',
            'email' => 'antigo@example.com',
            'phone' => '11988887777',
            'document' => '12345678901',
            'birth_date' => '1990-01-01',
            'gender' => GenderType::MALE->value,
            'visibility' => 'visible',
        ]);

        $payload = $this->validPayload($plan);
        $payload['client_id'] = $client->id;
        $payload['email'] = 'novo@example.com';

        $response = $this->actingAs($user)->post(route('contracts.store'), $payload);

        $response->assertRedirect(route('contracts.index'));
        $this->assertDatabaseCount('clients', 1);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'email' => 'novo@example.com',
        ]);
        $this->assertDatabaseCount('contracts', 1);
    }

    public function test_contract_wizard_can_find_client_by_document(): void
    {
        $user = User::factory()->create();
        $this->grantHiringPermissions($user);

        $client = Client::query()->create([
            'name' => 'Cliente Localizado',
            'email' => 'localizado@example.com',
            'phone' => '11988887777',
            'document' => '12345678901',
            'birth_date' => '1990-01-01',
            'gender' => GenderType::MALE->value,
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user)->getJson(route('contracts.find-client', [
            'document' => '123.456.789-01',
        ]));

        $response->assertOk()
            ->assertJson([
                'client' => [
                    'id' => $client->id,
                    'name' => 'Cliente Localizado',
                    'document' => '12345678901',
                ],
            ]);
    }

    public function test_coupon_can_be_loaded_by_code_for_contract_wizard(): void
    {
        $user = User::factory()->create();
        $this->grantHiringPermissions($user);

        $coupon = Coupon::query()->create([
            'code' => 'BEMVINDO',
            'percent' => 10,
            'discount_limit' => 100,
            'duration' => 30,
            'expiration_date' => '2026-12-31',
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user)->getJson(route('contracts.find-coupon', [
            'code' => 'BEMVINDO',
        ]));

        $response->assertOk()
            ->assertJson([
                'coupon' => [
                    'id' => $coupon->id,
                    'code' => 'BEMVINDO',
                ],
            ]);
    }

    public function test_contract_wizard_validates_the_selected_plan_duration_combination(): void
    {
        $user = User::factory()->create();
        $this->grantHiringPermissions($user);
        $plan = $this->createPlan();

        $payload = $this->validPayload($plan);
        $payload['installments'] = 6;

        $response = $this->actingAs($user)->post(route('contracts.store'), $payload);

        $response->assertSessionHasErrors(['installments']);
        $this->assertDatabaseCount('clients', 0);
        $this->assertDatabaseCount('contracts', 0);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Modality;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\PlanModality;
use App\Models\PlanTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanPersistenceTest extends TestCase
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

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        $planCategory = PlanCategory::query()->create([
            'name' => 'Premium',
            'visibility' => 'visible',
        ]);

        return [
            'name' => 'Plano Gold',
            'plan_category_id' => $planCategory->id,
            'description' => 'Plano com durações variáveis.',
            'tiers' => [
                ['quantity' => 1, 'price' => 99.9],
                ['quantity' => 12, 'price' => 999.9],
            ],
            'plan_modalities' => [],
        ];
    }

    public function test_authenticated_users_can_create_plan_with_required_tiers_and_empty_modalities(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'plans.create');

        $response = $this->actingAs($user)->post(route('plans.store'), $this->validPayload());

        $response->assertRedirect(route('plans.index'));

        $plan = Plan::query()->with(['tiers', 'modalities'])->firstOrFail();

        $this->assertSame('Plano Gold', $plan->name);
        $this->assertSame(2, $plan->tiers->count());
        $this->assertCount(0, $plan->modalities);

        $this->assertDatabaseHas('plan_tiers', [
            'plan_id' => $plan->id,
            'quantity' => 1,
            'price' => 99.9,
        ]);
    }

    public function test_tiers_are_required_when_creating_a_plan(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'plans.create');

        $payload = $this->validPayload();
        $payload['tiers'] = [];

        $response = $this->actingAs($user)->post(route('plans.store'), $payload);

        $response->assertSessionHasErrors(['tiers']);
        $this->assertDatabaseCount('plans', 0);
        $this->assertDatabaseCount('plan_tiers', 0);
    }

    public function test_authenticated_users_can_update_plan_and_clear_specific_modalities(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'plans.update');

        $planCategory = PlanCategory::query()->create([
            'name' => 'Premium',
            'visibility' => 'visible',
        ]);

        $firstModality = Modality::query()->create([
            'name' => 'Musculação',
            'visibility' => 'visible',
        ]);

        $secondModality = Modality::query()->create([
            'name' => 'Pilates',
            'visibility' => 'visible',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plano Inicial',
            'plan_category_id' => $planCategory->id,
            'description' => 'Descrição inicial',
            'visibility' => 'visible',
        ]);

        PlanTier::query()->create([
            'plan_id' => $plan->id,
            'quantity' => 3,
            'price' => 250,
        ]);

        PlanModality::query()->create([
            'plan_id' => $plan->id,
            'modality_id' => $firstModality->id,
        ]);

        PlanModality::query()->create([
            'plan_id' => $plan->id,
            'modality_id' => $secondModality->id,
        ]);

        $response = $this->actingAs($user)->put(route('plans.update', $plan), [
            'name' => 'Plano Atualizado',
            'plan_category_id' => $planCategory->id,
            'description' => 'Sem modalidades específicas.',
            'tiers' => [
                ['quantity' => 6, 'price' => 450],
            ],
            'plan_modalities' => [],
        ]);

        $response->assertRedirect(route('plans.index'));

        $plan->refresh();

        $this->assertSame('Plano Atualizado', $plan->name);
        $this->assertDatabaseHas('plan_tiers', [
            'plan_id' => $plan->id,
            'quantity' => 6,
            'price' => 450,
        ]);
        $this->assertDatabaseMissing('plan_tiers', [
            'plan_id' => $plan->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseCount('plan_modalities', 0);
    }
}

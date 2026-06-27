<?php

namespace Tests\Feature;

use App\Enums\BillableStatus;
use App\Enums\FinancialAccountType;
use App\Enums\GenderType;
use App\Enums\OperationType;
use App\Models\Client;
use App\Models\CostCenter;
use App\Models\DirectLesson;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\PlanCategory;
use App\Models\Permission;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ModuleDetailsRouteTest extends TestCase
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

    public function test_authenticated_users_can_visit_cost_center_details(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'cost_centers.view');

        $costCenter = CostCenter::query()->create([
            'name' => 'Centro Principal',
            'color' => '#123456',
            'operation_type' => OperationType::PAYABLE->value,
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user)->get(route('cost-centers.show', $costCenter));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('cost_centers/Details')
            ->where('cost-center.id', $costCenter->id)
            ->where('cost-center.name', 'Centro Principal')
        );
    }

    public function test_authenticated_users_can_visit_financial_category_details(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'financial_categories.view');

        $costCenter = CostCenter::query()->create([
            'name' => 'Centro Financeiro',
            'color' => '#654321',
            'operation_type' => OperationType::RECEIVABLE->value,
            'visibility' => 'visible',
        ]);

        $financialCategory = FinancialCategory::query()->create([
            'name' => 'Mensalidades',
            'color' => '#abcdef',
            'operation_type' => OperationType::RECEIVABLE->value,
            'cost_center_id' => $costCenter->id,
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user)->get(route('financial-categories.show', $financialCategory));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('financial_categories/Details')
            ->where('financial-category.id', $financialCategory->id)
            ->where('financial-category.name', 'Mensalidades')
        );
    }

    public function test_authenticated_users_can_visit_direct_lesson_details(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'direct_lessons.view');

        $client = Client::factory()->create();

        $trainer = Trainer::query()->create([
            'name' => 'Treinador Teste',
            'email' => 'treinador@example.com',
            'document' => '12345678901',
            'birth_date' => '1990-01-01',
            'phone' => '11999999999',
            'gender' => GenderType::MALE->value,
            'visibility' => 'visible',
        ]);

        $directLesson = DirectLesson::query()->create([
            'lesson_date' => '2026-06-27',
            'status' => BillableStatus::OPEN->value,
            'visibility' => 'visible',
            'price' => 120.50,
            'client_id' => $client->id,
            'trainer_id' => $trainer->id,
        ]);

        $response = $this->actingAs($user)->get(route('direct-lessons.show', $directLesson));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('direct_lessons/Details')
            ->where('direct-lesson.id', $directLesson->id)
        );
    }

    public function test_authenticated_users_can_visit_financial_account_details(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'financial_accounts.view');

        $financialAccount = FinancialAccount::query()->create([
            'name' => 'Caixa Principal',
            'account_type' => FinancialAccountType::CASH->value,
            'balance' => 1000,
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user)->get(route('financial-accounts.show', $financialAccount));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('financial_accounts/Details')
            ->where('financial-account.id', $financialAccount->id)
            ->where('financial-account.name', 'Caixa Principal')
        );
    }

    public function test_authenticated_users_can_visit_plan_category_details(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'plan_categories.view');

        $planCategory = PlanCategory::query()->create([
            'name' => 'Premium',
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user)->get(route('plan-categories.show', $planCategory));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('plan_categories/Details')
            ->where('plan-category.id', $planCategory->id)
            ->where('plan-category.name', 'Premium')
        );
    }
}

<?php

namespace Tests\Feature;

use App\AccessControl\AccessRole;
use App\Models\Permission;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_manager_roles_receive_report_view_permission(): void
    {
        $this->artisan('access-control:sync --without-users')->assertSuccessful();

        $admin = Role::query()
            ->where('name', AccessRole::ADMINISTRATOR->value)
            ->firstOrFail();

        $manager = Role::query()
            ->where('name', AccessRole::MANAGER->value)
            ->firstOrFail();

        $this->assertTrue($admin->permissions()->where('name', 'reports.view')->exists());
        $this->assertTrue($manager->permissions()->where('name', 'reports.view')->exists());
        $this->assertFalse($admin->permissions()->where('name', 'reports.update')->exists());
        $this->assertFalse($manager->permissions()->where('name', 'reports.update')->exists());
    }

    public function test_authorized_users_can_list_reports_without_visibility_filter(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'reports.view');

        Report::query()->create([
            'name' => 'financial_summary',
            'label' => 'Resumo financeiro',
            'description' => 'Relatório financeiro básico',
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('reports/Index')
            ->where('reports.data.0.name', 'financial_summary')
            ->where('reports.data.0.label', 'Resumo financeiro')
        );
    }

    public function test_authorized_users_can_open_report_detail_with_view_permission(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'reports.view');

        $report = Report::query()->create([
            'name' => 'client_activity',
            'label' => 'Atividade de clientes',
            'description' => 'Relatório de atividade',
        ]);

        $response = $this->actingAs($user)->get(route('reports.show', $report));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('reports/Details')
            ->where('report.id', $report->id)
            ->where('report.name', 'client_activity')
            ->where('report.label', 'Atividade de clientes')
        );
    }

    public function test_report_update_route_is_not_registered(): void
    {
        $this->assertFalse(Route::has('reports.update'));
    }

    protected function grantPermission(User $user, string $permissionName): void
    {
        $permission = Permission::query()->create([
            'name' => $permissionName,
            'description' => $permissionName,
        ]);

        $user->permissions()->attach($permission);
    }
}

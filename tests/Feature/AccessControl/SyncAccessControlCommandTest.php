<?php

namespace Tests\Feature\AccessControl;

use App\AccessControl\AccessModule;
use App\AccessControl\AccessRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncAccessControlCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_roles_permissions_role_permissions_and_users(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->artisan('access-control:sync')
            ->assertSuccessful();

        $this->assertSame(AccessRole::cases(), array_map(
            fn (Role $role) => AccessRole::from($role->name),
            Role::query()->orderBy('id')->get()->all(),
        ));

        $this->assertDatabaseHas('permissions', [
            'name' => 'clients.view',
            'description' => 'Clientes - Ver',
        ]);

        $administrator = Role::query()
            ->where('name', AccessRole::ADMINISTRATOR->value)
            ->firstOrFail();

        $this->assertTrue($administrator->permissions()->where('name', 'clients.delete')->exists());

        $this->assertSame(
            $administrator->id,
            $user->fresh()->role_id,
        );
    }

    public function test_it_is_idempotent(): void
    {
        $this->artisan('access-control:sync')->assertSuccessful();

        $roleCount = Role::query()->count();
        $permissionCount = Permission::query()->count();
        $permissionRoleCount = $this->permissionRoleCount();

        $this->artisan('access-control:sync')->assertSuccessful();

        $this->assertSame($roleCount, Role::query()->count());
        $this->assertSame($permissionCount, Permission::query()->count());
        $this->assertSame($permissionRoleCount, $this->permissionRoleCount());
    }

    public function test_it_can_skip_user_updates(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->artisan('access-control:sync --without-users')
            ->assertSuccessful();

        $this->assertNull($user->fresh()->role_id);
    }

    public function test_it_fails_with_invalid_default_role(): void
    {
        $this->artisan('access-control:sync --default-role=invalid')
            ->assertFailed();
    }

    public function test_it_creates_all_module_action_permissions(): void
    {
        $this->artisan('access-control:sync --without-users')->assertSuccessful();

        $expectedPermissionNames = collect(AccessModule::cases())
            ->flatMap(fn (AccessModule $module) => collect($module->actions())
                ->map(fn ($action) => $module->value.'.'.$action->value))
            ->sort()
            ->values()
            ->all();

        $permissionNames = Permission::query()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expectedPermissionNames, $permissionNames);
    }

    private function permissionRoleCount(): int
    {
        return (int) Role::query()
            ->withCount('permissions')
            ->get()
            ->sum('permissions_count');
    }
}

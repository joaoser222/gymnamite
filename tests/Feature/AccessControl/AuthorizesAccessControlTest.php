<?php

namespace Tests\Feature\AccessControl;

use App\AccessControl\RolePermissionMap;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizesAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_required_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('clients.index'));

        $response->assertForbidden();
    }

    public function test_user_with_direct_permission_is_authorized(): void
    {
        $user = User::factory()->create();
        $permission = $this->createPermission('clients.view');

        $user->permissions()->attach($permission);

        $response = $this->actingAs($user)->get(route('clients.index'));

        $response->assertOk();
    }

    public function test_user_with_role_permission_is_authorized(): void
    {
        $role = Role::query()->create([
            'name' => 'manager',
            'description' => 'Manager',
        ]);

        $permission = $this->createPermission('clients.view');

        $role->permissions()->attach($permission);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $response = $this->actingAs($user)->get(route('clients.index'));

        $response->assertOk();
    }

    public function test_role_permission_map_returns_string_actions(): void
    {
        $map = (new RolePermissionMap)->getMap();

        foreach ($map as $rolePermissions) {
            foreach ($rolePermissions as $actions) {
                foreach ($actions as $action) {
                    $this->assertIsString($action);
                }
            }
        }
    }

    private function createPermission(string $name): Permission
    {
        return Permission::query()->create([
            'name' => $name,
            'description' => $name,
        ]);
    }
}

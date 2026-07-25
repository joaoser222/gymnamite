<?php

namespace Tests\Feature\Auth;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_permissions_endpoint(): void
    {
        $response = $this->get(route('auth.permissions'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_fetch_their_cached_permissions_payload(): void
    {
        $role = Role::query()->create([
            'name' => 'manager',
            'description' => 'Manager',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $directPermission = Permission::query()->create([
            'name' => 'clients.view',
            'description' => 'clients.view',
        ]);

        $rolePermission = Permission::query()->create([
            'name' => 'users.view',
            'description' => 'users.view',
        ]);

        $user->permissions()->attach($directPermission);
        $role->permissions()->attach($rolePermission);

        $response = $this->actingAs($user)->getJson(route('auth.permissions'));

        $response
            ->assertOk()
            ->assertJsonPath('version', $user->permissionsVersion());

        $this->assertSame(['clients.view'], $response->json('permissions'));
    }

    public function test_permissions_version_changes_when_direct_permissions_change(): void
    {
        $user = User::factory()->create();

        $firstVersion = $user->permissionsVersion();

        $permission = Permission::query()->create([
            'name' => 'clients.view',
            'description' => 'clients.view',
        ]);

        $user->permissions()->attach($permission);

        $this->assertNotSame($firstVersion, $user->fresh()->permissionsVersion());
    }

    public function test_role_permissions_are_not_returned_in_cached_payload(): void
    {
        $role = Role::query()->create([
            'name' => 'manager',
            'description' => 'Manager',
        ]);

        $rolePermission = Permission::query()->create([
            'name' => 'users.view',
            'description' => 'users.view',
        ]);

        $customPermission = Permission::query()->create([
            'name' => 'clients.view',
            'description' => 'clients.view',
        ]);

        $role->permissions()->attach($rolePermission);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $user->permissions()->attach($customPermission);

        $response = $this->actingAs($user)->getJson(route('auth.permissions'));

        $response
            ->assertOk()
            ->assertJsonPath('permissions.0', 'clients.view');

        $this->assertSame(['clients.view'], $response->json('permissions'));
    }
}

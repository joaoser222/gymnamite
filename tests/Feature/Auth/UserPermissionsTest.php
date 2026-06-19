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
            ->assertJsonPath('version', $user->updated_at?->toISOString());

        $this->assertEqualsCanonicalizing(
            ['clients.view', 'users.view'],
            $response->json('permissions'),
        );
    }
}

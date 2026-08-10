<?php

namespace Tests\Feature\AccessControl;

use App\AccessControl\AccessRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_view_permission_can_list_and_view_fixed_roles(): void
    {
        $this->syncAccessControl();
        $user = User::factory()->create();
        $this->attachPermission($user, 'users.view');
        $manager = $this->role(AccessRole::MANAGER);

        $this->actingAs($user)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('roles/Index')
                ->has('roles.data', count(AccessRole::cases()))
                ->where('routes.index', route('roles.index'))
                ->where('routes.show', route('roles.show', ['role' => ':id']))
            );

        $this->actingAs($user)
            ->get(route('roles.show', $manager))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('roles/Details')
                ->where('role.id', $manager->id)
                ->where('isAdministrator', false)
                ->has('permissionGroups')
            );
    }

    public function test_role_creation_deletion_and_visibility_routes_are_not_exposed(): void
    {
        $this->syncAccessControl();
        $user = User::factory()->create();
        $this->attachPermission($user, 'users.update');

        $this->actingAs($user)->post('/roles')->assertMethodNotAllowed();
        $this->actingAs($user)->delete('/roles')->assertMethodNotAllowed();
        $this->actingAs($user)->patch('/roles/change-visibility')->assertMethodNotAllowed();
    }

    public function test_role_permission_updates_apply_only_added_and_removed_permissions_to_users(): void
    {
        $manager = Role::query()->create([
            'name' => AccessRole::MANAGER->value,
            'description' => AccessRole::MANAGER->label(),
        ]);
        $keptPermission = Permission::query()->create([
            'name' => 'test.kept',
            'description' => 'Permissão mantida em teste',
        ]);
        $removedPermission = Permission::query()->create([
            'name' => 'test.removed',
            'description' => 'Permissão removida em teste',
        ]);
        $editor = User::factory()->create();
        $this->attachPermission($editor, 'users.update');
        $manager->permissions()->attach([$keptPermission->id, $removedPermission->id]);
        $addedPermission = Permission::query()->create([
            'name' => 'test.added',
            'description' => 'Permissão adicionada em teste',
        ]);
        $unrelatedPermission = Permission::query()->create([
            'name' => 'test.unrelated',
            'description' => 'Permissão individual em teste',
        ]);
        $roleUser = User::factory()->create(['role_id' => $manager->id]);
        $roleUser->permissions()->attach([
            $keptPermission->id,
            $removedPermission->id,
            $unrelatedPermission->id,
        ]);

        $this->actingAs($editor)
            ->put(route('roles.update', $manager), [
                'permission_ids' => [$keptPermission->id, $addedPermission->id],
            ])
            ->assertRedirect(route('roles.show', $manager));

        $this->assertFalse($manager->fresh()->permissions()->whereKey($removedPermission->id)->exists());
        $this->assertTrue($manager->fresh()->permissions()->whereKey($addedPermission->id)->exists());
        $this->assertTrue($roleUser->fresh()->permissions()->whereKey($keptPermission->id)->exists());
        $this->assertFalse($roleUser->fresh()->permissions()->whereKey($removedPermission->id)->exists());
        $this->assertTrue($roleUser->fresh()->permissions()->whereKey($addedPermission->id)->exists());
        $this->assertTrue($roleUser->fresh()->permissions()->whereKey($unrelatedPermission->id)->exists());
    }

    public function test_administrator_permissions_cannot_be_updated(): void
    {
        $this->syncAccessControl();
        $editor = User::factory()->create();
        $this->attachPermission($editor, 'users.update');
        $administrator = $this->role(AccessRole::ADMINISTRATOR);
        $permissionIds = $administrator->permissions()->pluck('permissions.id')->all();

        $this->actingAs($editor)
            ->put(route('roles.update', $administrator), [
                'permission_ids' => array_slice($permissionIds, 1),
            ])
            ->assertSessionHasErrors('role');

        $this->assertEqualsCanonicalizing(
            $permissionIds,
            $administrator->fresh()->permissions()->pluck('permissions.id')->all(),
        );
    }

    public function test_role_permission_update_requires_valid_permission_ids(): void
    {
        $this->syncAccessControl();
        $editor = User::factory()->create();
        $this->attachPermission($editor, 'users.update');

        $this->actingAs($editor)
            ->put(route('roles.update', $this->role(AccessRole::MANAGER)), [
                'permission_ids' => [999999],
            ])
            ->assertSessionHasErrors('permission_ids.0');
    }

    private function syncAccessControl(): void
    {
        $this->artisan('access-control:sync --without-users')->assertSuccessful();
    }

    private function role(AccessRole $accessRole): Role
    {
        return Role::query()->where('name', $accessRole->value)->firstOrFail();
    }

    private function attachPermission(User $user, string $name): void
    {
        $permission = Permission::query()->firstOrCreate(
            ['name' => $name],
            ['description' => $name],
        );

        $user->permissions()->syncWithoutDetaching([$permission->id]);
    }
}

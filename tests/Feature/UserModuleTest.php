<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserModuleTest extends TestCase
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

    public function test_authenticated_users_can_visit_users_index(): void
    {
        $admin = User::factory()->create();
        $this->grantPermission($admin, 'users.view');

        $role = Role::query()->create([
            'name' => 'Gerente',
            'description' => 'Gerente geral',
        ]);

        $listedUser = User::factory()->create([
            'name' => 'Maria Gestora',
            'email' => 'maria@example.com',
            'role_id' => $role->id,
        ]);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('users/Index')
            ->has('users.data')
            ->where('users.data.0.id', $listedUser->id)
            ->where('users.data.0.role.description', 'Gerente geral')
            ->where('routes.index', route('users.index'))
        );
    }

    public function test_authenticated_users_can_visit_user_details(): void
    {
        $admin = User::factory()->create();
        $this->grantPermission($admin, 'users.view');

        $role = Role::query()->create([
            'name' => 'Atendente',
            'description' => 'Perfil operacional',
        ]);

        $user = User::factory()->create([
            'name' => 'Joao Operador',
            'email' => 'joao@example.com',
            'role_id' => $role->id,
        ]);

        $permission = Permission::query()->create([
            'name' => 'clients.view',
            'description' => 'Clientes - Ver',
        ]);

        $rolePermission = Permission::query()->create([
            'name' => 'users.update',
            'description' => 'Usuários - Atualizar',
        ]);

        $role->permissions()->attach($rolePermission);
        $role->permissions()->attach($permission);

        $response = $this->actingAs($admin)->get(route('users.show', $user));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('users/Details')
            ->where('user.id', $user->id)
            ->where('user.name', 'Joao Operador')
            ->where('user.email', 'joao@example.com')
            ->where('user.role_id', $role->id)
            ->where('options.roles.0.label', 'Perfil operacional')
            ->where('selectedPermissionIds.0', $rolePermission->id)
            ->where('selectedPermissionIds.1', $permission->id)
            ->where('options.editablePermissionIdsByRole.'.$role->id.'.0', $rolePermission->id)
            ->where('options.permissionGroups.0.permissions.0.id', $permission->id)
        );
    }

    public function test_authenticated_users_can_create_users(): void
    {
        $admin = User::factory()->create();
        $this->grantPermission($admin, 'users.create');

        $role = Role::query()->create([
            'name' => 'Supervisor',
            'description' => 'Perfil supervisor',
        ]);

        $permission = Permission::query()->create([
            'name' => 'clients.view',
            'description' => 'Clientes - Ver',
        ]);

        $role->permissions()->attach($permission);

        $permissionOutsideRole = Permission::query()->create([
            'name' => 'settings.update',
            'description' => 'settings.update',
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Novo Usuario',
            'email' => 'novo.usuario@example.com',
            'role_id' => $role->id,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'permission_ids' => [$permission->id, $permissionOutsideRole->id],
        ]);

        $response->assertRedirect(route('users.index'));

        $createdUser = User::query()
            ->where('email', 'novo.usuario@example.com')
            ->first();

        $this->assertNotNull($createdUser);
        $this->assertSame('Novo Usuario', $createdUser->name);
        $this->assertSame($role->id, $createdUser->role_id);
        $this->assertTrue(Hash::check('secure-password', $createdUser->password));
        $this->assertEquals([$permission->id], $createdUser->permissions()->pluck('permissions.id')->all());
    }

    public function test_authenticated_users_can_update_users_without_changing_password(): void
    {
        $admin = User::factory()->create();
        $this->grantPermission($admin, 'users.update');

        $currentRole = Role::query()->create([
            'name' => 'Financeiro',
            'description' => 'Perfil financeiro',
        ]);

        $newRole = Role::query()->create([
            'name' => 'Comercial',
            'description' => 'Perfil comercial',
        ]);

        $oldPermission = Permission::query()->create([
            'name' => 'clients.view',
            'description' => 'clients.view',
        ]);

        $newPermission = Permission::query()->create([
            'name' => 'suppliers.view',
            'description' => 'suppliers.view',
        ]);

        $keptPermission = Permission::query()->create([
            'name' => 'sales.view',
            'description' => 'sales.view',
        ]);

        $currentRole->permissions()->attach([$oldPermission->id, $keptPermission->id]);
        $newRole->permissions()->attach([$newPermission->id, $keptPermission->id]);

        $user = User::factory()->create([
            'name' => 'Usuario Antigo',
            'email' => 'usuario.antigo@example.com',
            'role_id' => $currentRole->id,
            'password' => 'password',
        ]);

        $currentPasswordHash = $user->password;

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Usuario Atualizado',
            'email' => 'usuario.atualizado@example.com',
            'role_id' => $newRole->id,
            'password' => '',
            'password_confirmation' => '',
            'permission_ids' => [$keptPermission->id],
        ]);

        $response->assertRedirect(route('users.index'));

        $user->refresh();

        $this->assertSame('Usuario Atualizado', $user->name);
        $this->assertSame('usuario.atualizado@example.com', $user->email);
        $this->assertSame($newRole->id, $user->role_id);
        $this->assertSame($currentPasswordHash, $user->password);
        $this->assertEquals([$keptPermission->id], $user->permissions()->pluck('permissions.id')->all());
    }

    public function test_guests_are_redirected_from_users_index(): void
    {
        $response = $this->get(route('users.index'));

        $response->assertRedirect(route('login'));
    }
}

<?php

namespace Tests\Feature\Actions;

use App\Actions\Users\SaveUserWithPermissionsAction;
use App\DTOs\Users\SaveUserWithPermissionsDTO;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SaveUserWithPermissionsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_user_with_permissions(): void
    {
        $role = Role::query()->create(['name' => 'Gerente', 'description' => 'Gerente']);
        $permission = Permission::query()->create(['name' => 'clients.view', 'description' => 'Ver clientes']);
        $role->permissions()->attach($permission);

        $action = app(SaveUserWithPermissionsAction::class);
        $dto = new SaveUserWithPermissionsDTO(
            name: 'Novo Usuario',
            email: 'novo@test.com',
            role_id: $role->id,
            password: 'secret123',
            permission_ids: [$permission->id],
        );

        $result = $action->execute($dto);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame('Novo Usuario', $result->name);
        $this->assertSame('novo@test.com', $result->email);
        $this->assertTrue(Hash::check('secret123', $result->password));
        $this->assertEquals([$permission->id], $result->permissions()->pluck('permissions.id')->all());
    }

    public function test_filters_permissions_outside_role(): void
    {
        $role = Role::query()->create(['name' => 'Atendente', 'description' => 'Atendente']);
        $allowedPermission = Permission::query()->create(['name' => 'clients.view', 'description' => 'Ver']);
        $deniedPermission = Permission::query()->create(['name' => 'settings.update', 'description' => 'Config']);
        $role->permissions()->attach($allowedPermission);

        $action = app(SaveUserWithPermissionsAction::class);
        $dto = new SaveUserWithPermissionsDTO(
            name: 'Usuario',
            email: 'user@test.com',
            role_id: $role->id,
            password: 'pass123',
            permission_ids: [$allowedPermission->id, $deniedPermission->id],
        );

        $result = $action->execute($dto);

        $this->assertEquals([$allowedPermission->id], $result->permissions()->pluck('permissions.id')->all());
    }

    public function test_creates_user_with_role_but_no_extra_permissions(): void
    {
        $role = Role::query()->create(['name' => 'Atendente', 'description' => 'Atendente']);

        $action = app(SaveUserWithPermissionsAction::class);
        $dto = new SaveUserWithPermissionsDTO(
            name: 'Basico',
            email: 'basico@test.com',
            role_id: $role->id,
            password: 'pass123',
        );

        $result = $action->execute($dto);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($role->id, $result->role_id);
        $this->assertDatabaseHas('users', ['email' => 'basico@test.com']);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(SaveUserWithPermissionsAction::class);
        $action->execute('not-a-dto');
    }
}

<?php

namespace Tests\Feature\Actions;

use App\AccessControl\AccessRole;
use App\Actions\Roles\UpdateRolePermissionsAction;
use App\DTOs\Roles\UpdateRolePermissionsDTO;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateRolePermissionsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_syncs_permissions_for_a_role(): void
    {
        $role = Role::query()->create(['name' => AccessRole::MANAGER->value, 'description' => 'Gerente']);
        $p1 = Permission::query()->create(['name' => 'clients.view', 'description' => 'Ver']);
        $p2 = Permission::query()->create(['name' => 'clients.create', 'description' => 'Criar']);
        $role->permissions()->attach($p1);

        $action = app(UpdateRolePermissionsAction::class);
        $dto = new UpdateRolePermissionsDTO(
            role_id: $role->id,
            permission_ids: [$p1->id, $p2->id],
        );

        $result = $action->execute($dto);

        $this->assertInstanceOf(Role::class, $result);
        $this->assertEquals(
            [$p1->id, $p2->id],
            $result->permissions->pluck('id')->sort()->values()->all(),
        );
    }

    public function test_syncs_permissions_to_users_with_same_role(): void
    {
        $role = Role::query()->create(['name' => AccessRole::MANAGER->value, 'description' => 'Gerente']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $p1 = Permission::query()->create(['name' => 'clients.view', 'description' => 'Ver']);
        $p2 = Permission::query()->create(['name' => 'sales.view', 'description' => 'Ver vendas']);

        $action = app(UpdateRolePermissionsAction::class);
        $dto = new UpdateRolePermissionsDTO(
            role_id: $role->id,
            permission_ids: [$p1->id, $p2->id],
        );

        $action->execute($dto);

        $this->assertEquals(
            [$p1->id, $p2->id],
            $user->fresh()->permissions()->pluck('permissions.id')->sort()->values()->all(),
        );
    }

    public function test_removes_permissions_from_users_when_role_loses_them(): void
    {
        $role = Role::query()->create(['name' => AccessRole::MANAGER->value, 'description' => 'Gerente']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $p1 = Permission::query()->create(['name' => 'clients.view', 'description' => 'Ver']);
        $p2 = Permission::query()->create(['name' => 'sales.view', 'description' => 'Ver vendas']);
        $role->permissions()->attach([$p1->id, $p2->id]);
        $user->permissions()->attach([$p1->id, $p2->id]);

        $action = app(UpdateRolePermissionsAction::class);
        $dto = new UpdateRolePermissionsDTO(
            role_id: $role->id,
            permission_ids: [$p1->id],
        );

        $action->execute($dto);

        $this->assertEquals(
            [$p1->id],
            $user->fresh()->permissions()->pluck('permissions.id')->all(),
        );
    }

    public function test_throws_for_administrator_role(): void
    {
        $this->expectException(ValidationException::class);

        $role = Role::query()->create(['name' => AccessRole::ADMINISTRATOR->value, 'description' => 'Admin']);
        $action = app(UpdateRolePermissionsAction::class);
        $dto = new UpdateRolePermissionsDTO(role_id: $role->id, permission_ids: []);
        $action->execute($dto);
    }

    public function test_throws_for_non_configurable_role(): void
    {
        $this->expectException(ValidationException::class);

        $role = Role::query()->create(['name' => 'CustomRole', 'description' => 'Invalid']);
        $action = app(UpdateRolePermissionsAction::class);
        $dto = new UpdateRolePermissionsDTO(role_id: $role->id, permission_ids: []);
        $action->execute($dto);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(UpdateRolePermissionsAction::class);
        $action->execute('not-a-dto');
    }
}

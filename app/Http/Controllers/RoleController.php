<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\AccessControl\AccessRole;
use App\Actions\Roles\UpdateRolePermissionsAction;
use App\DTOs\Roles\UpdateRolePermissionsDTO;
use App\Http\Requests\RolePermissionRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends AbstractModuleController
{
    public function __construct(
        private readonly UpdateRolePermissionsAction $updateRolePermissions,
    ) {}

    protected function accessModule(): AccessModule
    {
        return AccessModule::USER;
    }

    protected function modelClass(): string
    {
        return Role::class;
    }

    /**
     * @return array<string, string>
     */
    protected function getModuleRoutes(): array
    {
        return [
            'index' => route('roles.index'),
            'show' => str_replace('__id__', ':id', route('roles.show', ['role' => '__id__'])),
            'update' => str_replace('__id__', ':id', route('roles.update', ['role' => '__id__'])),
        ];
    }

    public function index(Request $request): Response|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        $roles = Role::query()
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($roles);
        }

        return Inertia::render('roles/Index', [
            'roles' => $roles,
            'filters' => [
                'page' => (int) $request->input('page', 1),
                'search' => '',
                'searchField' => '',
                'visibility' => 'visible',
                'sortBy' => 'id',
            ],
            'routes' => $this->getModuleRoutes(),
        ]);
    }

    public function show(Request $request, Role $role): Response|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        if ($request->expectsJson()) {
            return response()->json($role->load('permissions:id'));
        }

        return Inertia::render('roles/Details', [
            'role' => $role,
            'selectedPermissionIds' => $role->permissions()->pluck('permissions.id')->all(),
            'permissionGroups' => $this->permissionGroups(),
            'routes' => $this->getModuleRoutes(),
            'isAdministrator' => $role->name === AccessRole::ADMINISTRATOR->value,
        ]);
    }

    public function update(RolePermissionRequest $request, Role $role): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        $updatedRole = $this->updateRolePermissions->execute(new UpdateRolePermissionsDTO(
            role_id: $role->id,
            permission_ids: $request->validated('permission_ids'),
        ));

        if ($request->expectsJson()) {
            return response()->json($updatedRole);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Permissões do perfil atualizadas com sucesso.',
        ]);

        return redirect()->route('roles.show', $role);
    }

    /**
     * @return array<int, array{id: string, title: string, permissions: array<int, array{id: int, name: string, label: string, description: string}>}>
     */
    private function permissionGroups(): array
    {
        $permissions = Permission::query()
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return collect(AccessModule::cases())
            ->map(function (AccessModule $module) use ($permissions): ?array {
                $modulePermissions = $permissions
                    ->filter(fn (Permission $permission): bool => str_starts_with($permission->name, $module->value.'.'))
                    ->map(function (Permission $permission): array {
                        $action = AccessAction::tryFrom((string) str($permission->name)->afterLast('.'));

                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'label' => $action?->label() ?? $permission->name,
                            'description' => $permission->description,
                        ];
                    })
                    ->values()
                    ->all();

                if ($modulePermissions === []) {
                    return null;
                }

                return [
                    'id' => $module->value,
                    'title' => $module->label(),
                    'permissions' => $modulePermissions,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}

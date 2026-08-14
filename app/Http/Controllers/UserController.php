<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Users\SaveUserWithPermissionsAction;
use App\DTOs\Users\SaveUserWithPermissionsDTO;
use App\Http\Requests\UserRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends CrudModuleController
{
    public function __construct(
        private readonly SaveUserWithPermissionsAction $saveUserWithPermissions,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'email', 'role_id', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name', 'email'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'email', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::USER;
    }

    protected function modelClass(): string
    {
        return User::class;
    }

    protected function storeRequestClass(): ?string
    {
        return UserRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return UserRequest::class;
    }

    protected function newModelQuery(): Builder
    {
        return User::query()->with(['role:id,description', 'permissions:id,name,description']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function moduleDetailsProps(?Model $model = null): array
    {
        $user = $model instanceof User ? $model->loadMissing('role.permissions:id') : null;

        return [
            'selectedPermissionIds' => $user instanceof User
                ? ($user->effectivePermissionIds()->isNotEmpty()
                    ? $user->effectivePermissionIds()->all()
                    : $user->editablePermissionIds()->all())
                : [],
            'options' => [
                'roles' => Role::query()
                    ->orderBy('name')
                    ->get(['id', 'description'])
                    ->map(fn (Role $role): array => [
                        'value' => $role->id,
                        'label' => $role->description,
                    ])
                    ->all(),
                'editablePermissionIdsByRole' => $this->rolePermissionsById(),
                'permissionGroups' => $this->permissionGroups(),
            ],
        ];
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $user = $this->saveUserWithPermissions->execute(
            SaveUserWithPermissionsDTO::from(
                $this->validatedRequestData($request, $this->storeRequestClass()),
            ),
        );

        if ($request->expectsJson()) {
            return response()->json($user->load(['role:id,name', 'permissions:id,name,description']), 201);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' criado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        /** @var User $user */
        $user = $this->modelFromRoute($request);
        $this->saveUserWithPermissions->execute(
            SaveUserWithPermissionsDTO::from([
                ...$this->validatedRequestData($request, $this->updateRequestClass()),
                'id' => $user->getKey(),
            ]),
        );

        if ($request->expectsJson()) {
            return response()->json($user->fresh()->load(['role:id,name', 'permissions:id,name,description']));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' atualizado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    /**
     * @return array<int, array{id: int, title: string, permissions: array<int, array{id: int, name: string, label: string, description: string}>}>
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

    /**
     * @return array<int, array<int, int>>
     */
    private function rolePermissionsById(): array
    {
        return Role::query()
            ->with('permissions:id')
            ->get(['id'])
            ->mapWithKeys(fn (Role $role): array => [
                $role->id => $role->permissions->pluck('id')->all(),
            ])
            ->all();
    }
}

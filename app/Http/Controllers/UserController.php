<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Http\Requests\UserRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UserController extends Controller
{
    use HasModule;

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

        $payload = $this->validatedRequestData($request, $this->storeRequestClass());
        $permissionIds = Arr::pull($payload, 'permission_ids', []);
        $permissionIds = $this->resolveUserPermissions(
            $payload['role_id'] ?? null,
            $permissionIds,
        );

        $user = DB::transaction(function () use ($payload, $permissionIds): User {
            $user = $this->newModelQuery()->create($payload);
            $user->permissions()->sync($permissionIds);

            return $user;
        });

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
        $payload = $this->validatedRequestData($request, $this->updateRequestClass());
        $permissionIds = Arr::pull($payload, 'permission_ids', []);
        $permissionIds = $this->resolveUserPermissions(
            $payload['role_id'] ?? $user->role_id,
            $permissionIds,
        );

        DB::transaction(function () use ($user, $payload, $permissionIds): void {
            $user->update($payload);
            $user->permissions()->sync($permissionIds);
        });

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

    /**
     * @param  array<int, int|string>  $selectedPermissionIds
     * @return array<int, int>
     */
    private function resolveUserPermissions(?int $roleId, array $selectedPermissionIds): array
    {
        $editablePermissionIds = collect($this->rolePermissionsById()[$roleId] ?? [])
            ->map(fn (int $permissionId): int => $permissionId)
            ->values();

        $selectedIds = collect($selectedPermissionIds)
            ->map(fn (int|string $permissionId): int => (int) $permissionId)
            ->filter(fn (int $permissionId): bool => $editablePermissionIds->contains($permissionId))
            ->unique()
            ->values();

        return $selectedIds->all();
    }
}

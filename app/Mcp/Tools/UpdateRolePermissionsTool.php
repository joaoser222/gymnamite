<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Roles\UpdateRolePermissionsAction;
use App\DTOs\Roles\UpdateRolePermissionsDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Name('update-role-permissions')]
#[Description('Atualiza as permissões de um cargo')]
#[IsIdempotent(true)]
class UpdateRolePermissionsTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdateRolePermissionsAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'role_id' => 'required|integer|min:1',
            'permission_ids' => 'required|array|min:1',
            'permission_ids.*' => 'required|integer|min:1',
        ]);

        try {
            $dto = UpdateRolePermissionsDTO::from($validated);
            $role = $this->action->execute($dto);

            return Response::json([
                'id' => $role->id,
                'name' => $role->name,
            ]);
        } catch (Throwable $e) {
            return Response::error('Erro ao atualizar permissões: ' . $e->getMessage());
        }
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('users.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'role_id' => $schema->integer()->description('ID do cargo')->required(),
            'permission_ids' => $schema->array()->description('Lista de IDs de permissões')->required(),
        ];
    }
}

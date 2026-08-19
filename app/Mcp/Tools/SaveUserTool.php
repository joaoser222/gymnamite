<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Users\SaveUserWithPermissionsAction;
use App\DTOs\Users\SaveUserWithPermissionsDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Name('save-user')]
#[Description('Cria ou atualiza um usuário com permissões')]
#[IsIdempotent(true)]
class SaveUserTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected SaveUserWithPermissionsAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|min:1',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role_id' => 'nullable|integer|min:1',
            'password' => 'nullable|string|min:8',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'integer|min:1',
        ]);

        try {
            $dto = SaveUserWithPermissionsDTO::from($validated);
            $user = $this->action->execute($dto);

            return Response::json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
        } catch (Throwable $e) {
            return Response::error('Erro ao salvar usuário: ' . $e->getMessage());
        }
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('users.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID do usuário (null para criar)')->nullable(),
            'name' => $schema->string()->description('Nome do usuário')->required(),
            'email' => $schema->string()->description('E-mail do usuário')->required(),
            'role_id' => $schema->integer()->description('ID do cargo')->nullable(),
            'password' => $schema->string()->description('Senha (mínimo 8 caracteres)')->nullable(),
            'permission_ids' => $schema->array()->description('Lista de IDs de permissões')->nullable(),
        ];
    }
}

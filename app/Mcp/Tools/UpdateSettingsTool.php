<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Settings\UpdateSettingsAction;
use App\DTOs\Settings\UpdateSettingsDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Name('update-settings')]
#[Description('Atualiza configurações do sistema')]
#[IsIdempotent(true)]
class UpdateSettingsTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdateSettingsAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'required',
        ]);

        try {
            $dto = UpdateSettingsDTO::from(['settings' => $validated['settings']]);
            $count = $this->action->execute($dto);

            return Response::json([
                'updated' => $count,
            ]);
        } catch (Throwable $e) {
            return Response::error('Erro ao atualizar configurações: ' . $e->getMessage());
        }
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('settings.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'settings' => $schema->array()->description('Configurações a atualizar (chave => valor)')->required(),
        ];
    }
}

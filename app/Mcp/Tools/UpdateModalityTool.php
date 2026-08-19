<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Modalities\UpdateModalityAction;
use App\DTOs\Modalities\UpdateModalityDTO;
use App\Models\Modality;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('update-modality')]
#[Description('Atualiza uma modalidade existente')]
#[IsIdempotent(true)]
class UpdateModalityTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdateModalityAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'name' => 'required|string|max:255',
        ]);

        $modality = Modality::find($validated['id']);

        if (! $modality) {
            return Response::error('Modalidade não encontrada.');
        }

        $dto = UpdateModalityDTO::from(array_merge(
            ['id' => $modality->id],
            array_filter($validated, fn ($v) => $v !== null, ARRAY_FILTER_USE_KEY),
        ));

        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data->id,
            'name' => $result->data->name,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('modalities.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID da modalidade')->required(),
            'name' => $schema->string()->description('Novo nome da modalidade')->required(),
        ];
    }
}

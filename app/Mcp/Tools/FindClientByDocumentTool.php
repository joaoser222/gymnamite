<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Contracts\FindClientAction;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('find-client-by-document')]
#[Description('Busca um cliente pelo CPF/CNPJ')]
#[IsIdempotent(true)]
class FindClientByDocumentTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected FindClientAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'document' => 'required|string|max:20',
        ]);

        $result = $this->action->execute($validated['document']);

        if (! $result->success) {
            return Response::error($result->message);
        }

        if (! $result->data) {
            return Response::error('Cliente não encontrado para o documento informado.');
        }

        return Response::json([
            'id' => $result->data->id,
            'name' => $result->data->name,
            'email' => $result->data->email,
            'document' => $result->data->document,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('contracts.view') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'document' => $schema->string()->description('CPF ou CNPJ do cliente')->required(),
        ];
    }
}

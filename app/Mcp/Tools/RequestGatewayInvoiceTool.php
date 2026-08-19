<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Receivables\RequestGatewayInvoiceAction;
use App\DTOs\Receivables\RequestGatewayInvoiceDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('request-gateway-invoice')]
#[Description('Solicita emissão de nota fiscal via gateway para um recebível')]
#[IsIdempotent(false)]
class RequestGatewayInvoiceTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected RequestGatewayInvoiceAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
        ]);

        $dto = RequestGatewayInvoiceDTO::from($validated);
        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data->id ?? $result->data['id'] ?? null,
            'status' => $result->data->status ?? $result->data['status'] ?? null,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('receivables.request_invoice') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID do recebível')->required(),
        ];
    }
}

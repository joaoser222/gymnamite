<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Receivables\MarkReceivablePaidAction;
use App\DTOs\Receivables\MarkReceivablePaidDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('mark-receivable-paid')]
#[Description('Marca uma recebível como pago')]
#[IsIdempotent(true)]
class MarkReceivablePaidTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected MarkReceivablePaidAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'payment_date' => 'required|date',
        ]);

        $dto = MarkReceivablePaidDTO::from($validated);
        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data->id,
            'due_date' => $result->data->due_date?->format('Y-m-d'),
            'payment_date' => $result->data->payment_date?->format('Y-m-d'),
            'total' => $result->data->total,
            'status' => $result->data->status->value,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('receivables.mark_paid') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID do recebível')->required(),
            'payment_date' => $schema->string()->description('Data do pagamento (YYYY-MM-DD)')->required(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Sales\CreateSaleAction;
use App\DTOs\Sales\CreateSaleDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Name('create-sale')]
#[Description('Cria uma nova venda no sistema')]
#[IsIdempotent(false)]
class CreateSaleTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreateSaleAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'client_id' => 'required|integer|min:1',
            'payment_method' => 'required|in:pix,boleto,credit_card,debit_card,cash,transfer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        try {
            $dto = CreateSaleDTO::fromValidatedData($validated);
            $sale = $this->action->execute($dto);

            return Response::json([
                'id' => $sale->id,
                'total' => $sale->total,
                'status' => $sale->status,
                'payment_method' => $sale->payment_method,
            ]);
        } catch (Throwable $e) {
            return Response::error('Erro ao criar venda: ' . $e->getMessage());
        }
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('sales.create') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'client_id' => $schema->integer()->description('ID do cliente')->required(),
            'payment_method' => $schema->string()->description('Método de pagamento (pix, boleto, credit_card, debit_card, cash, transfer)')->required(),
            'items' => $schema->array()->description('Itens da venda')->required(),
        ];
    }
}

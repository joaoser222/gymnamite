<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Sales\UpdateSaleAction;
use App\DTOs\Sales\UpdateSaleDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use App\Models\Sale;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Name('update-sale')]
#[Description('Atualiza uma venda existente')]
#[IsIdempotent(true)]
class UpdateSaleTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdateSaleAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'sale_id' => 'required|integer|min:1',
            'payment_method' => 'nullable|in:pix,boleto,credit_card,debit_card,cash,transfer',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        try {
            $sale = Sale::findOrFail($validated['sale_id']);
            $dto = UpdateSaleDTO::fromValidatedData($sale, $validated);
            $sale = $this->action->execute($dto);

            return Response::json([
                'id' => $sale->id,
                'total' => $sale->total,
                'status' => $sale->status,
                'payment_method' => $sale->payment_method,
            ]);
        } catch (Throwable $e) {
            return Response::error('Erro ao atualizar venda: ' . $e->getMessage());
        }
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('sales.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'sale_id' => $schema->integer()->description('ID da venda')->required(),
            'payment_method' => $schema->string()->description('Método de pagamento (pix, boleto, credit_card, debit_card, cash, transfer)')->nullable(),
            'items' => $schema->array()->description('Itens da venda')->nullable(),
        ];
    }
}

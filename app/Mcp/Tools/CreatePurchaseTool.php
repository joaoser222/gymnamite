<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Purchases\CreatePurchaseAction;
use App\DTOs\Purchases\CreatePurchaseDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Name('create-purchase')]
#[Description('Cria uma nova compra no sistema')]
#[IsIdempotent(false)]
class CreatePurchaseTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreatePurchaseAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'supplier_id' => 'required|integer|min:1',
            'payment_method' => 'required|in:pix,boleto,credit_card,debit_card,cash,transfer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        try {
            $dto = CreatePurchaseDTO::fromValidatedData($validated);
            $purchase = $this->action->execute($dto);

            return Response::json([
                'id' => $purchase->id,
                'total' => $purchase->total,
                'status' => $purchase->status,
                'payment_method' => $purchase->payment_method,
            ]);
        } catch (Throwable $e) {
            return Response::error('Erro ao criar compra: ' . $e->getMessage());
        }
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('purchases.create') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'supplier_id' => $schema->integer()->description('ID do fornecedor')->required(),
            'payment_method' => $schema->string()->description('Método de pagamento (pix, boleto, credit_card, debit_card, cash, transfer)')->required(),
            'items' => $schema->array()->description('Itens da compra')->required(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Purchases\UpdatePurchaseAction;
use App\DTOs\Purchases\UpdatePurchaseDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use App\Models\Purchase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Name('update-purchase')]
#[Description('Atualiza uma compra existente')]
#[IsIdempotent(true)]
class UpdatePurchaseTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdatePurchaseAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'purchase_id' => 'required|integer|min:1',
            'payment_method' => 'nullable|in:pix,boleto,credit_card,debit_card,cash,transfer',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        try {
            $purchase = Purchase::findOrFail($validated['purchase_id']);
            $dto = UpdatePurchaseDTO::fromValidatedData($purchase, $validated);
            $purchase = $this->action->execute($dto);

            return Response::json([
                'id' => $purchase->id,
                'total' => $purchase->total,
                'status' => $purchase->status,
                'payment_method' => $purchase->payment_method,
            ]);
        } catch (Throwable $e) {
            return Response::error('Erro ao atualizar compra: ' . $e->getMessage());
        }
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('purchases.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'purchase_id' => $schema->integer()->description('ID da compra')->required(),
            'payment_method' => $schema->string()->description('Método de pagamento (pix, boleto,credit_card, debit_card, cash, transfer)')->nullable(),
            'items' => $schema->array()->description('Itens da compra')->nullable(),
        ];
    }
}

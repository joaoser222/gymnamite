<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Products\UpdateProductAction;
use App\DTOs\Products\UpdateProductDTO;
use App\Models\Product;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('update-product')]
#[Description('Atualiza um produto existente')]
#[IsIdempotent(true)]
class UpdateProductTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdateProductAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'name' => 'nullable|string|max:255',
            'purchase_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'product_type' => 'nullable|in:input,output',
            'product_unity' => 'nullable|string|max:10',
            'quantity' => 'nullable|integer|min:0',
        ]);

        $product = Product::find($validated['id']);

        if (! $product) {
            return Response::error('Produto não encontrado.');
        }

        $dto = UpdateProductDTO::from(array_merge(
            ['id' => $product->id],
            array_filter($validated, fn ($v) => $v !== null, ARRAY_FILTER_USE_KEY),
        ));

        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data->id,
            'name' => $result->data->name,
            'purchase_price' => $result->data->purchase_price,
            'sale_price' => $result->data->sale_price,
            'quantity' => $result->data->quantity,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('products.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID do produto')->required(),
            'name' => $schema->string()->description('Novo nome do produto')->nullable(),
            'purchase_price' => $schema->number()->description('Novo preço de compra')->nullable(),
            'sale_price' => $schema->number()->description('Novo preço de venda')->nullable(),
            'product_type' => $schema->string()->description('Novo tipo do produto (input ou output)')->nullable(),
            'product_unity' => $schema->string()->description('Nova unidade do produto')->nullable(),
            'quantity' => $schema->integer()->description('Nova quantidade em estoque')->nullable(),
        ];
    }
}

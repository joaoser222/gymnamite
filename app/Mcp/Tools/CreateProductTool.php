<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Products\CreateProductAction;
use App\DTOs\Products\CreateProductDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('create-product')]
#[Description('Cria um novo produto no sistema')]
#[IsIdempotent(false)]
class CreateProductTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreateProductAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'product_type' => 'required|in:input,output',
            'product_unity' => 'required|string|max:10',
            'quantity' => 'nullable|integer|min:0',
        ]);

        $dto = CreateProductDTO::from($validated);
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
        return auth()->user()?->can('products.create') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Nome do produto')->required(),
            'purchase_price' => $schema->number()->description('Preço de compra')->required(),
            'sale_price' => $schema->number()->description('Preço de venda')->required(),
            'product_type' => $schema->string()->description('Tipo do produto (input ou output)')->required(),
            'product_unity' => $schema->string()->description('Unidade do produto (ex: kg, un, lt)')->required(),
            'quantity' => $schema->integer()->description('Quantidade em estoque')->nullable(),
        ];
    }
}

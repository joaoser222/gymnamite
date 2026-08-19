<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Supplier\CreateSupplierAction;
use App\DTOs\Supplier\CreateSupplierDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('create-supplier')]
#[Description('Cria um novo fornecedor no sistema')]
#[IsIdempotent(false)]
class CreateSupplierTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreateSupplierAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'document' => 'required|string|max:20',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'address_number' => 'nullable|string|max:50',
            'address_complement' => 'nullable|string|max:255',
            'address_state' => 'nullable|string|max:2',
            'address_city' => 'nullable|string|max:255',
            'address_district' => 'nullable|string|max:255',
            'address_postal_code' => 'nullable|string|max:10',
        ]);

        $dto = CreateSupplierDTO::from($validated);
        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
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
        return auth()->user()?->can('suppliers.create') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Nome do fornecedor')->required(),
            'email' => $schema->string()->description('E-mail do fornecedor')->nullable(),
            'document' => $schema->string()->description('CNPJ ou documento do fornecedor')->required(),
            'phone' => $schema->string()->description('Telefone do fornecedor')->nullable(),
            'address' => $schema->string()->description('Logradouro do endereço')->nullable(),
            'address_number' => $schema->string()->description('Número do endereço')->nullable(),
            'address_complement' => $schema->string()->description('Complemento do endereço')->nullable(),
            'address_state' => $schema->string()->description('Estado (UF, 2 caracteres)')->nullable(),
            'address_city' => $schema->string()->description('Cidade do endereço')->nullable(),
            'address_district' => $schema->string()->description('Bairro do endereço')->nullable(),
            'address_postal_code' => $schema->string()->description('CEP do endereço')->nullable(),
        ];
    }
}

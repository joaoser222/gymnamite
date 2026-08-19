<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Supplier\UpdateSupplierAction;
use App\DTOs\Supplier\UpdateSupplierDTO;
use App\Models\Supplier;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('update-supplier')]
#[Description('Atualiza um fornecedor existente')]
#[IsIdempotent(true)]
class UpdateSupplierTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdateSupplierAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'document' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'address_number' => 'nullable|string|max:50',
            'address_complement' => 'nullable|string|max:255',
            'address_state' => 'nullable|string|max:2',
            'address_city' => 'nullable|string|max:255',
            'address_district' => 'nullable|string|max:255',
            'address_postal_code' => 'nullable|string|max:10',
        ]);

        $supplier = Supplier::find($validated['id']);

        if (! $supplier) {
            return Response::error('Fornecedor não encontrado.');
        }

        $dto = UpdateSupplierDTO::from(array_merge(
            ['id' => $supplier->id],
            array_filter($validated, fn ($v) => $v !== null, ARRAY_FILTER_USE_KEY),
        ));

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
        return auth()->user()?->can('suppliers.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID do fornecedor')->required(),
            'name' => $schema->string()->description('Novo nome do fornecedor')->nullable(),
            'email' => $schema->string()->description('Novo e-mail do fornecedor')->nullable(),
            'document' => $schema->string()->description('Novo CNPJ ou documento do fornecedor')->nullable(),
            'phone' => $schema->string()->description('Novo telefone do fornecedor')->nullable(),
            'address' => $schema->string()->description('Novo logradouro do endereço')->nullable(),
            'address_number' => $schema->string()->description('Novo número do endereço')->nullable(),
            'address_complement' => $schema->string()->description('Novo complemento do endereço')->nullable(),
            'address_state' => $schema->string()->description('Novo estado (UF, 2 caracteres)')->nullable(),
            'address_city' => $schema->string()->description('Nova cidade do endereço')->nullable(),
            'address_district' => $schema->string()->description('Novo bairro do endereço')->nullable(),
            'address_postal_code' => $schema->string()->description('Novo CEP do endereço')->nullable(),
        ];
    }
}

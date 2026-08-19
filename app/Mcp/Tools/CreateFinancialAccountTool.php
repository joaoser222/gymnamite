<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\FinancialAccounts\CreateFinancialAccountAction;
use App\DTOs\FinancialAccounts\CreateFinancialAccountDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('create-financial-account')]
#[Description('Cria uma nova conta financeira')]
#[IsIdempotent(false)]
class CreateFinancialAccountTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreateFinancialAccountAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account_type' => 'required|in:bank,cash',
            'holder_name' => 'nullable|string|max:255',
            'holder_document' => 'nullable|string|max:20',
            'holder_birth_date' => 'nullable|date',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_agency' => 'nullable|string|max:50',
            'bank_account_type' => 'nullable|string|max:20',
            'bank_code' => 'nullable|string|max:20',
        ]);

        $dto = CreateFinancialAccountDTO::from($validated);
        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data->id,
            'name' => $result->data->name,
            'account_type' => $result->data->account_type,
            'balance' => $result->data->balance,
            'holder_name' => $result->data->holder_name,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('financial_accounts.create') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Nome da conta financeira')->required(),
            'account_type' => $schema->string()->description('Tipo de conta (bank ou cash)')->required(),
            'holder_name' => $schema->string()->description('Nome do titular da conta')->nullable(),
            'holder_document' => $schema->string()->description('Documento do titular (CPF/CNPJ)')->nullable(),
            'holder_birth_date' => $schema->string()->description('Data de nascimento do titular')->nullable(),
            'bank_account_number' => $schema->string()->description('Número da conta bancária')->nullable(),
            'bank_agency' => $schema->string()->description('Número da agência bancária')->nullable(),
            'bank_account_type' => $schema->string()->description('Tipo de conta bancária')->nullable(),
            'bank_code' => $schema->string()->description('Código do banco')->nullable(),
        ];
    }
}

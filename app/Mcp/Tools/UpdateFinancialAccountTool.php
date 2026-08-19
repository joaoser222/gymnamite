<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\FinancialAccounts\UpdateFinancialAccountAction;
use App\DTOs\FinancialAccounts\UpdateFinancialAccountDTO;
use App\Models\FinancialAccount;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('update-financial-account')]
#[Description('Atualiza uma conta financeira existente')]
#[IsIdempotent(true)]
class UpdateFinancialAccountTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdateFinancialAccountAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'name' => 'nullable|string|max:255',
            'account_type' => 'nullable|in:bank,cash',
            'holder_name' => 'nullable|string|max:255',
            'holder_document' => 'nullable|string|max:20',
            'holder_birth_date' => 'nullable|date',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_agency' => 'nullable|string|max:50',
            'bank_account_type' => 'nullable|string|max:20',
            'bank_code' => 'nullable|string|max:20',
        ]);

        $financialAccount = FinancialAccount::find($validated['id']);

        if (! $financialAccount) {
            return Response::error('Conta financeira não encontrada.');
        }

        $dto = UpdateFinancialAccountDTO::from(array_merge(
            ['id' => $financialAccount->id],
            array_filter($validated, fn ($v) => $v !== null, ARRAY_FILTER_USE_KEY),
        ));

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
        return auth()->user()?->can('financial_accounts.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID da conta financeira')->required(),
            'name' => $schema->string()->description('Novo nome da conta financeira')->nullable(),
            'account_type' => $schema->string()->description('Novo tipo de conta (bank ou cash)')->nullable(),
            'holder_name' => $schema->string()->description('Novo nome do titular da conta')->nullable(),
            'holder_document' => $schema->string()->description('Novo documento do titular (CPF/CNPJ)')->nullable(),
            'holder_birth_date' => $schema->string()->description('Nova data de nascimento do titular')->nullable(),
            'bank_account_number' => $schema->string()->description('Novo número da conta bancária')->nullable(),
            'bank_agency' => $schema->string()->description('Novo número da agência bancária')->nullable(),
            'bank_account_type' => $schema->string()->description('Novo tipo de conta bancária')->nullable(),
            'bank_code' => $schema->string()->description('Novo código do banco')->nullable(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Payables\CreatePayableAction;
use App\DTOs\Payables\CreatePayableDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('create-payable')]
#[Description('Cria uma nova conta a pagar')]
#[IsIdempotent(false)]
class CreatePayableTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreatePayableAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'supplier_id' => 'required|integer|min:1',
            'due_date' => 'required|date',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'required|in:pix,boleto,credit_card,debit_card,cash,transfer',
            'operation_type' => 'required|in:entry,exit',
            'annotations' => 'nullable|string|max:500',
            'financial_account_id' => 'nullable|integer|min:1',
            'financial_category_id' => 'nullable|integer|min:1',
        ]);

        $dto = CreatePayableDTO::from($validated);
        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data->id,
            'due_date' => $result->data->due_date,
            'total' => $result->data->total,
            'status' => $result->data->status,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('payables.create') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'supplier_id' => $schema->integer()->description('ID do fornecedor')->required(),
            'due_date' => $schema->string()->description('Data de vencimento (YYYY-MM-DD)')->required(),
            'total' => $schema->number()->description('Valor total da conta a pagar')->required(),
            'payment_method' => $schema->string()->description('Forma de pagamento (pix, boleto, credit_card, debit_card, cash, transfer)')->required(),
            'operation_type' => $schema->string()->description('Tipo de operação (entry ou exit)')->required(),
            'annotations' => $schema->string()->description('Anotações ou observações')->nullable(),
            'financial_account_id' => $schema->integer()->description('ID da conta financeira associada')->nullable(),
            'financial_category_id' => $schema->integer()->description('ID da categoria financeira associada')->nullable(),
        ];
    }
}

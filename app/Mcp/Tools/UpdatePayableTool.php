<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Payables\UpdatePayableAction;
use App\DTOs\Payables\UpdatePayableDTO;
use App\Models\Payable;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('update-payable')]
#[Description('Atualiza uma conta a pagar existente')]
#[IsIdempotent(true)]
class UpdatePayableTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdatePayableAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'supplier_id' => 'nullable|integer|min:1',
            'due_date' => 'nullable|date',
            'total' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:pix,boleto,credit_card,debit_card,cash,transfer',
            'operation_type' => 'nullable|in:entry,exit',
            'annotations' => 'nullable|string|max:500',
            'financial_account_id' => 'nullable|integer|min:1',
            'financial_category_id' => 'nullable|integer|min:1',
        ]);

        $payable = Payable::find($validated['id']);

        if (! $payable) {
            return Response::error('Conta a pagar não encontrada.');
        }

        $dto = UpdatePayableDTO::from(array_merge(
            ['id' => $payable->id],
            array_filter($validated, fn ($v) => $v !== null, ARRAY_FILTER_USE_KEY),
        ));

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
        return auth()->user()?->can('payables.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID da conta a pagar')->required(),
            'supplier_id' => $schema->integer()->description('Novo ID do fornecedor')->nullable(),
            'due_date' => $schema->string()->description('Nova data de vencimento (YYYY-MM-DD)')->nullable(),
            'total' => $schema->number()->description('Novo valor total da conta a pagar')->nullable(),
            'payment_method' => $schema->string()->description('Nova forma de pagamento (pix, boleto, credit_card, debit_card, cash, transfer)')->nullable(),
            'operation_type' => $schema->string()->description('Novo tipo de operação (entry ou exit)')->nullable(),
            'annotations' => $schema->string()->description('Novas anotações ou observações')->nullable(),
            'financial_account_id' => $schema->integer()->description('Novo ID da conta financeira associada')->nullable(),
            'financial_category_id' => $schema->integer()->description('Novo ID da categoria financeira associada')->nullable(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;

#[Name('register-sale')]
#[Description('Guia o modelo a registrar uma venda de produto ou serviço para um cliente.')]
class RegisterSalePrompt extends Prompt
{
    public function shouldRegister(): bool
    {
        return auth()->user()?->can('sales.create') ?? false;
    }

    public function handle(Request $request): Response
    {
        return Response::text(<<<'TEXT'
Para registrar uma venda na academia:

1. Identifique o cliente (por ID ou documento) e os itens vendidos.
2. Use a ferramenta de criar venda informando cliente, itens, valores e forma de pagamento.
3. Se a venda gerar uma conta a receber, informe a condição de pagamento.
4. Confirme o valor total e o meio de pagamento com o usuário antes de finalizar.
TEXT);
    }
}

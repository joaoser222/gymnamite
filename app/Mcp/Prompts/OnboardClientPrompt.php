<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;

#[Name('onboard-client')]
#[Description('Guia o modelo a cadastrar um novo cliente e, na sequência, criar um contrato vinculado a um plano.')]
class OnboardClientPrompt extends Prompt
{
    public function shouldRegister(): bool
    {
        return auth()->user()?->can('clients.create') ?? false;
    }

    public function handle(Request $request): Response
    {
        return Response::text(<<<'TEXT'
Para realizar o onboard de um cliente na academia:

1. Colete os dados do cliente (nome, documento, e-mail e telefone).
2. Use a ferramenta de criar cliente para registrá-lo.
3. Pergunte ao cliente qual plano deseja e liste os planos disponíveis.
4. Use a ferramenta de criar contrato informando o ID do cliente, o ID do plano e a forma de pagamento.
5. Confirme os dados do contrato com o usuário antes de finalizar.
TEXT);
    }
}

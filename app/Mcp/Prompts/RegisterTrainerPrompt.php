<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;

#[Name('register-trainer')]
#[Description('Guia o modelo a cadastrar um novo treinador na academia.')]
class RegisterTrainerPrompt extends Prompt
{
    public function shouldRegister(): bool
    {
        return auth()->user()?->can('trainers.create') ?? false;
    }

    public function handle(Request $request): Response
    {
        return Response::text(<<<'TEXT'
Para cadastrar um treinador na academia:

1. Colete os dados do treinador (nome, e-mail, documento, telefone e dados de endereço).
2. Use a ferramenta de criar treinador informando os dados coletados.
3. Confirme o cadastro com o usuário antes de finalizar.
TEXT);
    }
}

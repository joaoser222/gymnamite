<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DirectLessons\CreateDirectLessonAction;
use App\DTOs\DirectLessons\CreateDirectLessonDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Name('create-direct-lesson')]
#[Description('Cria uma nova aula direta no sistema')]
#[IsIdempotent(false)]
class CreateDirectLessonTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreateDirectLessonAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'client_id' => 'required|integer|min:1',
            'trainer_id' => 'required|integer|min:1',
            'modality_id' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'payment_method' => 'required|in:pix,boleto,credit_card,debit_card,cash,transfer',
            'lesson_date' => 'required|date',
        ]);

        try {
            $dto = CreateDirectLessonDTO::fromValidatedData($validated);
            $directLesson = $this->action->execute($dto);

            return Response::json([
                'id' => $directLesson->id,
                'price' => $directLesson->price,
                'status' => $directLesson->status,
                'payment_method' => $directLesson->payment_method,
                'lesson_date' => $directLesson->lesson_date,
            ]);
        } catch (Throwable $e) {
            return Response::error('Erro ao criar aula direta: ' . $e->getMessage());
        }
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('direct_lessons.create') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'client_id' => $schema->integer()->description('ID do cliente')->required(),
            'trainer_id' => $schema->integer()->description('ID do personal trainer')->required(),
            'modality_id' => $schema->integer()->description('ID da modalidade')->required(),
            'price' => $schema->number()->description('Valor da aula')->required(),
            'payment_method' => $schema->string()->description('Método de pagamento (pix, boleto, credit_card, debit_card, cash, transfer)')->required(),
            'lesson_date' => $schema->string()->description('Data da aula (Y-m-d H:i:s)')->required(),
        ];
    }
}

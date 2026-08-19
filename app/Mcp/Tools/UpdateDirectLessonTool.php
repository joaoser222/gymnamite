<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DirectLessons\UpdateDirectLessonAction;
use App\DTOs\DirectLessons\UpdateDirectLessonDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use App\Models\DirectLesson;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Name('update-direct-lesson')]
#[Description('Atualiza uma aula direta existente')]
#[IsIdempotent(true)]
class UpdateDirectLessonTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdateDirectLessonAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'direct_lesson_id' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:pix,boleto,credit_card,debit_card,cash,transfer',
            'lesson_date' => 'nullable|date',
        ]);

        try {
            $directLesson = DirectLesson::findOrFail($validated['direct_lesson_id']);
            $dto = UpdateDirectLessonDTO::fromValidatedData($directLesson, $validated);
            $directLesson = $this->action->execute($dto);

            return Response::json([
                'id' => $directLesson->id,
                'price' => $directLesson->price,
                'status' => $directLesson->status,
                'payment_method' => $directLesson->payment_method,
                'lesson_date' => $directLesson->lesson_date,
            ]);
        } catch (Throwable $e) {
            return Response::error('Erro ao atualizar aula direta: ' . $e->getMessage());
        }
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('direct_lessons.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'direct_lesson_id' => $schema->integer()->description('ID da aula direta')->required(),
            'price' => $schema->number()->description('Valor da aula')->nullable(),
            'payment_method' => $schema->string()->description('Método de pagamento (pix, boleto, credit_card, debit_card, cash, transfer)')->nullable(),
            'lesson_date' => $schema->string()->description('Data da aula (Y-m-d H:i:s)')->nullable(),
        ];
    }
}

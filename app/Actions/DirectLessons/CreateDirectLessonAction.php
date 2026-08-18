<?php

namespace App\Actions\DirectLessons;

use App\Actions\BaseAction;
use App\DTOs\DirectLessons\CreateDirectLessonDTO;
use App\Models\DirectLesson;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class CreateDirectLessonAction extends BaseAction
{
    /** Authorization is performed by DirectLessonController. */
    protected string $ability = '';

    public function __construct(private readonly GenerateDirectLessonInvoicesAction $generateDirectLessonInvoices) {}

    protected function handle(mixed $input): mixed
    {
        if (! $input instanceof CreateDirectLessonDTO) {
            throw new InvalidArgumentException('CreateDirectLessonAction expects a CreateDirectLessonDTO.');
        }

        $data = $input->data;
        $generateInvoices = (bool) Arr::pull($data, 'generate_invoices', true);
        $directLesson = DirectLesson::query()->create($data);

        if ($generateInvoices) {
            $this->generateDirectLessonInvoices->execute($directLesson);
        }

        return $directLesson;
    }
}

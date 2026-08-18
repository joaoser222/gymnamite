<?php

namespace App\Actions\DirectLessons;

use App\Actions\BaseAction;
use App\Actions\Exceptions\UpdateBillableBlockedException;
use App\DTOs\DirectLessons\UpdateDirectLessonDTO;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class UpdateDirectLessonAction extends BaseAction
{
    /** Authorization is performed by DirectLessonController. */
    protected string $ability = '';

    public function __construct(private readonly GenerateDirectLessonInvoicesAction $generateDirectLessonInvoices) {}

    protected function handle(mixed $input): mixed
    {
        if (! $input instanceof UpdateDirectLessonDTO) {
            throw new InvalidArgumentException('UpdateDirectLessonAction expects an UpdateDirectLessonDTO.');
        }

        $directLesson = $input->directLesson;

        if ($directLesson->invoices()->whereHas('gatewayPayment')->exists()) {
            throw new UpdateBillableBlockedException(
                'Aulas avulsas com faturas vinculadas a transações no gateway não podem ser atualizadas.',
            );
        }

        $data = $input->data;
        $generateInvoices = (bool) Arr::pull($data, 'generate_invoices', true);

        $directLesson->invoices()->delete();
        $directLesson->update($data);

        if ($generateInvoices) {
            $this->generateDirectLessonInvoices->execute($directLesson->refresh());
        }

        return $directLesson->refresh();
    }
}

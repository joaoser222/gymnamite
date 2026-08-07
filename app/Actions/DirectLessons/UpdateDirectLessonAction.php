<?php

namespace App\Actions\DirectLessons;

use App\Actions\BaseAction;
use App\Actions\Exceptions\UpdateBillableBlockedException;
use App\DTOs\DirectLessons\UpdateDirectLessonDTO;
use App\Services\Billing\InvoiceGenerator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;

class UpdateDirectLessonAction extends BaseAction
{
    /** Authorization is performed by DirectLessonController. */
    protected string $ability = '';

    public function __construct(private readonly InvoiceGenerator $invoiceGenerator) {}

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
            $this->queueGatewayInvoiceSync($this->invoiceGenerator->generate($directLesson->refresh()));
        }

        return $directLesson->refresh();
    }

    private function queueGatewayInvoiceSync(Collection $invoices): void
    {
        $invoicesToSync = $invoices->filter(fn ($invoice): bool => $invoice->shouldGenerateGatewayTransaction());

        if ($invoicesToSync->isNotEmpty()) {
            Artisan::queue('gateway:sync-invoices', ['--invoice' => $invoicesToSync->modelKeys()])->afterCommit();
        }
    }
}

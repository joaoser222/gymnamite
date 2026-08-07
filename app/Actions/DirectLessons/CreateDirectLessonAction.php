<?php

namespace App\Actions\DirectLessons;

use App\Actions\BaseAction;
use App\DTOs\DirectLessons\CreateDirectLessonDTO;
use App\Models\DirectLesson;
use App\Services\Billing\InvoiceGenerator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;

class CreateDirectLessonAction extends BaseAction
{
    /** Authorization is performed by DirectLessonController. */
    protected string $ability = '';

    public function __construct(private readonly InvoiceGenerator $invoiceGenerator) {}

    protected function handle(mixed $input): mixed
    {
        if (! $input instanceof CreateDirectLessonDTO) {
            throw new InvalidArgumentException('CreateDirectLessonAction expects a CreateDirectLessonDTO.');
        }

        $data = $input->data;
        $generateInvoices = (bool) Arr::pull($data, 'generate_invoices', true);
        $directLesson = DirectLesson::query()->create($data);

        if ($generateInvoices) {
            $this->queueGatewayInvoiceSync($this->invoiceGenerator->generate($directLesson));
        }

        return $directLesson;
    }

    private function queueGatewayInvoiceSync(Collection $invoices): void
    {
        $invoicesToSync = $invoices->filter(fn ($invoice): bool => $invoice->shouldGenerateGatewayTransaction());

        if ($invoicesToSync->isNotEmpty()) {
            Artisan::queue('gateway:sync-invoices', ['--invoice' => $invoicesToSync->modelKeys()])->afterCommit();
        }
    }
}

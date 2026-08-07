<?php

namespace App\Actions\Receivables;

use App\Actions\BaseAction;
use App\DTOs\Receivables\ActionResultDTO;
use App\DTOs\Receivables\RequestGatewayInvoiceDTO;
use App\Models\Receivable;
use App\Services\Gateway\FiscalInvoiceEmitter;

class RequestGatewayInvoiceAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    public function __construct(
        private readonly FiscalInvoiceEmitter $fiscalInvoiceEmitter,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof RequestGatewayInvoiceDTO) {
            throw new \InvalidArgumentException('RequestGatewayInvoiceAction requires a RequestGatewayInvoiceDTO.');
        }

        $dto = $input;
        $receivable = Receivable::query()->findOrFail($dto->id);

        try {
            $gatewayInvoice = $this->fiscalInvoiceEmitter->emit($receivable);

            return ActionResultDTO::success(
                $gatewayInvoice,
                'Nota fiscal solicitada com sucesso.',
            );
        } catch (\Throwable $exception) {
            return ActionResultDTO::failure(
                $exception->getMessage(),
                ['gateway_invoice' => $exception->getMessage()],
            );
        }
    }
}

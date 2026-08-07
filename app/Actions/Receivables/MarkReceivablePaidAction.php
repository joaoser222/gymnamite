<?php

namespace App\Actions\Receivables;

use App\Actions\BaseAction;
use App\DTOs\Receivables\ActionResultDTO;
use App\DTOs\Receivables\MarkReceivablePaidDTO;
use App\Enums\InvoiceStatus;
use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Models\Movement;
use App\Models\Receivable;

class MarkReceivablePaidAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof MarkReceivablePaidDTO) {
            throw new \InvalidArgumentException('MarkReceivablePaidAction requires a MarkReceivablePaidDTO.');
        }

        $dto = $input;
        $receivable = Receivable::query()->findOrFail($dto->id);

        if ($receivable->status === InvoiceStatus::PAID) {
            return ActionResultDTO::failure(
                'Este recebimento já foi baixado.',
                ['id' => 'Este recebimento já foi baixado.'],
            );
        }

        $receivable->update([
            'payment_date' => $dto->payment_date,
            'paid_value' => $receivable->total,
            'status' => InvoiceStatus::PAID,
        ]);

        Movement::query()->create([
            'operation_type' => OperationType::RECEIVABLE,
            'movement_type' => $receivable->payment_method === PaymentMethod::CASH
                ? MovementType::INTERNAL
                : MovementType::EXTERNAL,
            'value' => $receivable->total,
            'invoice_id' => $receivable->id,
            'visibility' => 'visible',
        ]);

        return ActionResultDTO::success(
            $receivable->refresh(),
            'Recebimento baixado com sucesso.',
        );
    }
}

<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum InvoiceType: string
{
    use HasMetadata;

    case STANDARD = 'standard';
    case REFUND = 'refund';
    case CHARGEBACK = 'chargeback';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::STANDARD => 'Padrão',
            self::REFUND => 'Devolução',
            self::CHARGEBACK => 'Estorno',
            self::ADJUSTMENT => 'Ajuste'
        };
    }
}

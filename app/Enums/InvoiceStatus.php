<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum InvoiceStatus: string
{
    use HasMetadata;

    case PENDING = 'pending';
    case OVERDUED = 'overdued';
    case PAID = 'paid';
    case CANCELED = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::OVERDUED => 'Vencido',
            self::PAID => 'Pago',
            self::CANCELED => 'Cancelado'
        };
    }
}

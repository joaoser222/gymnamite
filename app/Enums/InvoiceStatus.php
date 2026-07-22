<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum InvoiceStatus: string
{
    use HasMetadata;

    case PENDING = 'pending';
    case WAITING = 'waiting';
    case OVERDUED = 'overdued';
    case PAID = 'paid';
    case CANCELED = 'canceled';

    protected static function fields(): array
    {
        return ['label', 'value', 'color'];
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::WAITING => 'Aguardando Pagamento',
            self::OVERDUED => 'Vencido',
            self::PAID => 'Pago',
            self::CANCELED => 'Cancelado'
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::WAITING => 'warning',
            self::OVERDUED => 'error',
            self::PAID => 'success',
            self::CANCELED => 'error',
        };
    }
}

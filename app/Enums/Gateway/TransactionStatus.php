<?php

namespace App\Enums\Gateway;

use App\Traits\HasMetadata;

enum TransactionStatus: string
{
    use HasMetadata;

    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case CANCELED = 'canceled';
    case OVERDUE = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Aguardando',
            self::PAID => 'Pago',
            self::FAILED => 'Falhou',
            self::REFUNDED => 'Estornado',
            self::CANCELED => 'Cancelado',
            self::OVERDUE => 'Vencido'
        };
    }
}

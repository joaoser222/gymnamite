<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum BillableStatus: string
{
    use HasMetadata;

    case OPEN = 'open';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';
    case RETURNED = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Aberto',
            self::COMPLETED => 'Finalizado',
            self::CANCELED => 'Cancelado',
            self::RETURNED => 'Devolvido',
        };
    }
}

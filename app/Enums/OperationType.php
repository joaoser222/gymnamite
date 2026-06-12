<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum OperationType: string
{
    use HasMetadata;
    case PAYABLE = 'payable';
    case RECEIVABLE = 'receivable';

    public function label(): string
    {
        return match ($this) {
            self::PAYABLE => 'Pagamento',
            self::RECEIVABLE => 'Recebimento'
        };
    }
}

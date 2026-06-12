<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum FinancialAccountType: string
{
    use HasMetadata;

    case CASH = 'cash';
    case BANK = 'bank';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Caixa',
            self::BANK => 'Conta Bancária'
        };
    }
}

<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum ProductType: string
{
    use HasMetadata;

    case MERCHANDISE = 'merchandise';
    case SERVICE = 'service';

    public function label(): string
    {
        return match ($this) {
            self::MERCHANDISE => 'Mercadoria',
            self::SERVICE => 'Serviço'
        };
    }
}

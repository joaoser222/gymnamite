<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum LegalType: string
{
    use HasMetadata;

    case COMPANY = 'company';
    case INDIVIDUAL = 'individual';

    public function label(): string
    {
        return match ($this) {
            self::COMPANY => 'Jurídico',
            self::INDIVIDUAL => 'Físico'
        };
    }
}

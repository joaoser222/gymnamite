<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum GenderType: string
{
    use HasMetadata;

    case MALE = 'M';
    case FEMALE = 'F';

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Masculino',
            self::FEMALE => 'Feminino'
        };
    }
}

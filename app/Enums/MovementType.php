<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum MovementType: string
{
    use HasMetadata;

    case INTERNAL = 'internal';
    case EXTERNAL = 'external';

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Interno',
            self::EXTERNAL => 'Externo'
        };
    }
}

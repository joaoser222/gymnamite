<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum ClientStatus: string
{
    use HasMetadata;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case OVERDUED = 'overdued';
    case LOCKED = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Ativo',
            self::INACTIVE => 'Inativo',
            self::OVERDUED => 'Em atraso',
            self::LOCKED => 'Bloqueado'
        };
    }
}

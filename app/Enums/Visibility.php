<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum Visibility: string
{
    use HasMetadata;

    case VISIBLE = 'visible';
    case HIDDEN = 'hidden';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::VISIBLE => 'Visível',
            self::HIDDEN => 'Oculto',
            self::ARCHIVED => 'Arquivado',
        };
    }
}

<?php

namespace App\Enums\Gateway;

use App\Traits\HasMetadata;

enum PostbackStatus: string
{
    use HasMetadata;

    case PENDING = 'pending';
    case FAILED = 'failed';
    case SUCCESS = 'success';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Aguardando',
            self::FAILED => 'Falhou',
            self::SUCCESS => 'Finalizado'
        };
    }
}

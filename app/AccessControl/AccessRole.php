<?php

namespace App\AccessControl;

use App\Traits\HasMetadata;

enum AccessRole: string
{
    use HasMetadata;

    case ADMINISTRATOR = 'administrator';
    case MANAGER = 'manager';
    case BILLING = 'billing';

    public function label(): string
    {
        return match ($this) {
            self::ADMINISTRATOR => 'Administrador',
            self::MANAGER => 'Gerente',
            self::BILLING => 'Faturamento'
        };
    }
}

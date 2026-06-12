<?php

namespace App\Enums;

use App\Traits\HasMetadata;

enum PaymentMethod: string
{
    use HasMetadata;

    case BOLETO = 'boleto';
    case PIX = 'pix';
    case CREDIT_CARD = 'credit_card';
    case CASH = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::BOLETO => 'Boleto',
            self::PIX => 'Pix',
            self::CREDIT_CARD => 'Cartão de Crédito',
            self::CASH => 'Dinheiro',
        };
    }
}

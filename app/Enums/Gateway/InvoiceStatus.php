<?php

namespace App\Enums\Gateway;

use App\Traits\HasMetadata;

enum InvoiceStatus: string
{
    use HasMetadata;

    case PENDING = 'pending';
    case SCHEDULED = 'scheduled';
    case PROCESSING = 'processing';
    case SYNCHRONIZED = 'synchronized';
    case AUTHORIZED = 'authorized';
    case PROCESSING_CANCELLATION = 'processing_cancellation';
    case CANCELLATION_DENIED = 'cancellation_denied';
    case CANCELED = 'canceled';
    case ERROR = 'error';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::SCHEDULED => 'Agendada',
            self::PROCESSING => 'Processando',
            self::SYNCHRONIZED => 'Sincronizada',
            self::AUTHORIZED => 'Autorizada',
            self::PROCESSING_CANCELLATION => 'Cancelamento em processamento',
            self::CANCELLATION_DENIED => 'Cancelamento negado',
            self::CANCELED => 'Cancelada',
            self::ERROR => 'Erro',
            self::UNKNOWN => 'Desconhecida',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AUTHORIZED => 'success',
            self::SYNCHRONIZED => 'success',
            self::CANCELED, self::ERROR, self::CANCELLATION_DENIED => 'error',
            self::PROCESSING_CANCELLATION, self::PROCESSING => 'info',
            self::SCHEDULED => 'info',
            self::PENDING, self::UNKNOWN => 'warning',
        };
    }
}

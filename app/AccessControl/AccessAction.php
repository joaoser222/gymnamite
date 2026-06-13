<?php

namespace App\AccessControl;

use App\Traits\HasMetadata;

enum AccessAction: string
{
    use HasMetadata;

    case VIEW = 'view';
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case VISIBILITY = 'visibility';
    case MARK_PAID = 'mark_paid';
    case MARK_UNPAID = 'mark_unpaid';
    case CANCEL = 'cancel';

    public function label(): string
    {
        return match ($this) {
            self::VIEW => 'Ver',
            self::CREATE => 'Criar',
            self::UPDATE => 'Atualizar',
            self::DELETE => 'Excluir',
            self::VISIBILITY => 'Visibilidade',
            self::MARK_PAID => 'Realizar Baixa',
            self::MARK_UNPAID => 'Estornar Baixa',
            self::CANCEL => 'Cancelar'
        };
    }
}

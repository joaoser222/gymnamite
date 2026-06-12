<?php

namespace App\Traits;

use App\Enums\Visibility;

trait HasVisibility
{
    /**
     * Inicializa a trait
     */
    public function initializeHasVisibility(): void
    {
        // Adiciona o cast para Visibility Enum
        if (! isset($this->casts['visibility'])) {
            $this->casts['visibility'] = Visibility::class;
        }

        // Adiciona visibility ao fillable se não existir
        if (! in_array('visibility', $this->fillable)) {
            $this->fillable[] = 'visibility';
        }

        // Define o valor padrão
        if (! isset($this->attributes['visibility'])) {
            $this->attributes['visibility'] = Visibility::VISIBLE;
        }
    }

    /**
     * Valida se o valor de visibility é válido
     */
    public function isValidVisibility(): bool
    {
        return Visibility::isValid($this->visibility);
    }

    /**
     * Retorna o objeto Enum
     */
    public function getVisibilityEnum(): ?Visibility
    {
        return Visibility::tryFrom($this->visibility);
    }

    /**
     * Acessor para label
     */
    public function getVisibilityLabelAttribute(): string
    {
        return $this->getVisibilityEnum()?->label() ?? $this->visibility;
    }

    /**
     * Mutator com validação
     */
    public function setVisibilityAttribute(string|Visibility $value): void
    {
        if ($value instanceof Visibility) {
            $value = $value->value;
        }

        if (! Visibility::isValid($value)) {
            throw new \InvalidArgumentException(
                "Valor inválido para visibility: '{$value}'. Valores permitidos: "
                .implode(', ', Visibility::values())
            );
        }

        $this->attributes['visibility'] = $value;
    }

    // Métodos de verificação

    public function isVisible(): bool
    {
        return $this->visibility === Visibility::VISIBLE->value;
    }

    public function isHidden(): bool
    {
        return $this->visibility === Visibility::HIDDEN->value;
    }

    public function isArchived(): bool
    {
        return $this->visibility === Visibility::ARCHIVED->value;
    }

    // Scopes
    public function scopeVisible($query)
    {
        return $query->where('visibility', Visibility::VISIBLE->value);
    }

    public function scopeHidden($query)
    {
        return $query->where('visibility', Visibility::HIDDEN->value);
    }

    public function scopeArchived($query)
    {
        return $query->where('visibility', Visibility::ARCHIVED->value);
    }

    public function scopeNotArchived($query)
    {
        return $query->where('visibility', '!=', Visibility::ARCHIVED->value);
    }

    // Métodos estáticos para usar nas validações

    public static function getVisibilityOptions(): array
    {
        return Visibility::options();
    }

    public static function getValidVisibilityValues(): array
    {
        return Visibility::values();
    }
}

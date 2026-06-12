<?php

namespace App\Traits;

trait HasMetadata
{
    abstract public function label(): string;

    /**
     * Retorna todos os valores como array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Retorna array para selects (value => label)
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * Verifica se um valor é válido
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values());
    }
}

<?php

namespace App\Traits;

trait HasMetadata
{
    abstract public function label(): string;

    public function color(): string
    {
        return 'secondary';
    }

    /**
     * Lista de campos que serão retornados em options().
     * Pode ser sobrescrita no enum que usa o trait.
     */
    protected static function fields(): array
    {
        return ['label', 'value', 'color'];
    }

    /**
     * Retorna todos os valores como array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Retorna array para selects (value => [campos definidos em fields()])
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $data = [];

            foreach (static::fields() as $field) {
                $data[$field] = $field === 'value'
                    ? $case->value
                    : $case->{$field}();
            }

            $options[$case->value] = $data;
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

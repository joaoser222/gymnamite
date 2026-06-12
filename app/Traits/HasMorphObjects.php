<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;

trait HasMorphObjects
{
    protected static function bootHasMorphObjects(): void
    {
        static::registerAllMorphMaps();
        static::creating(function ($model) {
            $model->validateAllMorphTypes();
        });
        static::updating(function ($model) {
            $model->validateAllMorphTypes();
        });
    }

    /**
     * Registra todos os morph maps definidos nas propriedades
     */
    protected static function registerAllMorphMaps(): void
    {
        static $registered = [];

        $class = static::class;

        if (isset($registered[$class])) {
            return;
        }

        // Registra maps da propriedade $morphMaps
        if (property_exists($class, 'morphMaps')) {
            foreach ((array) static::$morphMaps as $field => $map) {
                if (! empty($map)) {
                    Relation::morphMap($map);
                }
            }
        }

        $registered[$class] = true;
    }

    /**
     * Valida todos os campos morph type
     */
    protected function validateAllMorphTypes(): void
    {
        $configs = $this->getAllMorphConfigs();

        foreach ($configs as $config) {
            $field = $config['field'];
            $value = $this->getAttribute($field);

            if ($value !== null && ! $this->isMorphTypeAllowed($value, $config)) {
                throw ValidationException::withMessages([
                    $field => [$this->getMorphErrorMessage($value, $config)],
                ]);
            }
        }
    }

    /**
     * Retorna todas as configurações dos morphs
     */
    public function getAllMorphConfigs(): array
    {
        $configs = [];
        $class = static::class;

        // Pega os maps definidos
        $morphMaps = property_exists($class, 'morphMaps')
            ? static::$morphMaps
            : [];

        // Pega as permissões definidas
        $allowedTypes = property_exists($class, 'allowedMorphTypes')
            ? static::$allowedMorphTypes
            : [];

        // Para cada campo definido no morphMaps
        foreach ($morphMaps as $field => $map) {
            $configs[$field] = [
                'options' => array_keys($map),
                'allowed' => $allowedTypes[$field] ?? array_keys($map),
            ];
        }

        return $configs;
    }

    /**
     * Verifica se um tipo é permitido
     */
    protected function isMorphTypeAllowed(string $type, array $config): bool
    {
        $allowed = $config['allowed'] ?? [];

        // Se não definiu allowed explicitamente, permite todos do map
        if (empty($allowed)) {
            $allowed = array_keys($config['map'] ?? []);
        }

        return in_array($type, $allowed);
    }

    /**
     * Retorna mensagem de erro
     */
    protected function getMorphErrorMessage(string $invalidType, array $config): string
    {
        $field = $config['field'];
        $allowed = implode(', ', $config['allowed'] ?? []);

        return "Tipo '{$invalidType}' não é permitido para o campo '{$field}'. Tipos permitidos: {$allowed}";
    }

    /**
     * Método auxiliar para adicionar um tipo permitido dinamicamente
     */
    public function addAllowedMorphType(string $field, string $type): void
    {
        if (! property_exists($this, 'allowedMorphTypes')) {
            $this->allowedMorphTypes = [];
        }

        if (! isset($this->allowedMorphTypes[$field])) {
            $this->allowedMorphTypes[$field] = $this->getDefaultAllowedForField($field);
        }

        if (! in_array($type, $this->allowedMorphTypes[$field])) {
            $this->allowedMorphTypes[$field][] = $type;
        }
    }

    /**
     * Retorna os tipos permitidos para um campo
     */
    public function getAllowedForField(string $field): array
    {
        $allowed = property_exists($this, 'allowedMorphTypes')
            ? ($this->allowedMorphTypes[$field] ?? [])
            : [];

        if (empty($allowed) && property_exists($this, 'morphMaps')) {
            $allowed = array_keys($this->morphMaps[$field] ?? []);
        }

        return $allowed;
    }

    /**
     * Retorna os tipos padrão para um campo (todos do map)
     */
    protected function getDefaultAllowedForField(string $field): array
    {
        return property_exists($this, 'morphMaps')
            ? array_keys($this->morphMaps[$field] ?? [])
            : [];
    }
}

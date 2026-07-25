<?php

namespace App\Reports;

class ReportRegistry
{
    /**
     * @return array<int, ReportDefinition>
     */
    public static function all(): array
    {
        return [
            // Report definitions will be registered here.
        ];
    }

    public static function find(string $key): ?ReportDefinition
    {
        return collect(self::all())
            ->first(fn (ReportDefinition $definition): bool => $definition->key === $key);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function options(): array
    {
        return collect(self::all())
            ->map(fn (ReportDefinition $definition): array => $definition->toArray())
            ->values()
            ->all();
    }
}

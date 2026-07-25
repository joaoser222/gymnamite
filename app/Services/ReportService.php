<?php

namespace App\Services;

use App\Models\User;
use App\Reports\ReportDefinition;
use App\Reports\ReportRegistry;
use App\Reports\ReportResult;
use InvalidArgumentException;

class ReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        return ReportRegistry::options();
    }

    public function find(string $key): ?ReportDefinition
    {
        return ReportRegistry::find($key);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function run(string $key, array $filters = [], ?User $user = null): ReportResult
    {
        $definition = $this->find($key);

        if ($definition === null) {
            throw new InvalidArgumentException("Report [{$key}] is not registered.");
        }

        return new ReportResult(
            definition: $definition,
            columns: $definition->columns,
            rows: [],
            meta: [
                'filters' => $filters,
                'user_id' => $user?->id,
            ],
        );
    }
}

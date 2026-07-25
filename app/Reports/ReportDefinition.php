<?php

namespace App\Reports;

class ReportDefinition
{
    /**
     * @param  array<int, ReportFilter>  $filters
     * @param  array<int, ReportColumn>  $columns
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $description = '',
        public readonly array $filters = [],
        public readonly array $columns = [],
    ) {}

    /**
     * @return array{key: string, label: string, description: string, filters: array<int, array<string, mixed>>, columns: array<int, array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'filters' => array_map(
                fn (ReportFilter $filter): array => $filter->toArray(),
                $this->filters,
            ),
            'columns' => array_map(
                fn (ReportColumn $column): array => $column->toArray(),
                $this->columns,
            ),
        ];
    }
}

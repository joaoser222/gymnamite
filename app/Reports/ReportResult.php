<?php

namespace App\Reports;

class ReportResult
{
    /**
     * @param  array<int, ReportColumn>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly ReportDefinition $definition,
        public readonly array $columns = [],
        public readonly array $rows = [],
        public readonly array $meta = [],
    ) {}

    /**
     * @return array{definition: array<string, mixed>, columns: array<int, array<string, mixed>>, rows: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'definition' => $this->definition->toArray(),
            'columns' => array_map(
                fn (ReportColumn $column): array => $column->toArray(),
                $this->columns,
            ),
            'rows' => $this->rows,
            'meta' => $this->meta,
        ];
    }
}

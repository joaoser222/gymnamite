<?php

namespace App\Reports;

class ReportColumn
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly ?string $type = null,
        public readonly bool $sortable = false,
    ) {}

    /**
     * @return array{key: string, label: string, type: string|null, sortable: bool}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'sortable' => $this->sortable,
        ];
    }
}

<?php

namespace App\Reports;

class ReportFilter
{
    /**
     * @param  array<int, array{value: mixed, label: string}>  $options
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $type = 'string',
        public readonly bool $required = false,
        public readonly array $options = [],
    ) {}

    /**
     * @return array{key: string, label: string, type: string, required: bool, options: array<int, array{value: mixed, label: string}>}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'required' => $this->required,
            'options' => $this->options,
        ];
    }
}

<?php

namespace Tests\Unit\Reports;

use App\Reports\ReportColumn;
use App\Reports\ReportDefinition;
use App\Reports\ReportFilter;
use App\Reports\ReportRegistry;
use App\Services\ReportService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ReportServiceTest extends TestCase
{
    public function test_registry_starts_without_registered_reports(): void
    {
        $this->assertSame([], ReportRegistry::all());
        $this->assertSame([], (new ReportService)->definitions());
        $this->assertNull((new ReportService)->find('missing'));
    }

    public function test_definition_serializes_filters_and_columns(): void
    {
        $definition = new ReportDefinition(
            key: 'sample',
            label: 'Sample',
            description: 'Sample report',
            filters: [
                new ReportFilter('status', 'Status', options: [
                    ['value' => 'active', 'label' => 'Active'],
                ]),
            ],
            columns: [
                new ReportColumn('name', 'Name', sortable: true),
            ],
        );

        $this->assertSame([
            'key' => 'sample',
            'label' => 'Sample',
            'description' => 'Sample report',
            'filters' => [[
                'key' => 'status',
                'label' => 'Status',
                'type' => 'string',
                'required' => false,
                'options' => [[
                    'value' => 'active',
                    'label' => 'Active',
                ]],
            ]],
            'columns' => [[
                'key' => 'name',
                'label' => 'Name',
                'type' => null,
                'sortable' => true,
            ]],
        ], $definition->toArray());
    }

    public function test_service_rejects_unregistered_reports(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Report [missing] is not registered.');

        (new ReportService)->run('missing');
    }
}

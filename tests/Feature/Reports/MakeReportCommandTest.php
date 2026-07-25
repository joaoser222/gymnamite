<?php

namespace Tests\Feature\Reports;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MakeReportCommandTest extends TestCase
{
    private string $reportPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportPath = app_path('Reports/Definitions/GeneratedCommandTestReport.php');
        File::delete($this->reportPath);
    }

    protected function tearDown(): void
    {
        File::delete($this->reportPath);

        parent::tearDown();
    }

    public function test_it_creates_a_report_definition_class(): void
    {
        $this->artisan('make:report generated-command-test')
            ->assertSuccessful();

        $this->assertFileExists($this->reportPath);

        $contents = File::get($this->reportPath);

        $this->assertStringContainsString('class GeneratedCommandTestReport', $contents);
        $this->assertStringContainsString("key: 'generated_command_test'", $contents);
        $this->assertStringContainsString("label: 'Generated Command Test'", $contents);
    }

    public function test_it_does_not_overwrite_existing_reports_without_force(): void
    {
        $this->artisan('make:report generated-command-test')
            ->assertSuccessful();

        $this->artisan('make:report generated-command-test')
            ->assertFailed();
    }
}

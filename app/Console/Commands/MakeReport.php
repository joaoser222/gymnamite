<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

#[Signature('make:report {name : The report class name} {--force : Overwrite the report class if it already exists}')]
#[Description('Create a new report definition class')]
class MakeReport extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(Filesystem $files): int
    {
        $name = $this->reportName((string) $this->argument('name'));
        $path = app_path("Reports/Definitions/{$name}.php");

        if ($files->exists($path) && ! (bool) $this->option('force')) {
            $this->components->error("Report [{$name}] already exists.");

            return self::FAILURE;
        }

        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, $this->stub($name));

        $this->components->info("Report [app/Reports/Definitions/{$name}.php] created successfully.");

        return self::SUCCESS;
    }

    private function reportName(string $name): string
    {
        $class = Str::of($name)
            ->replace(['/', '\\'], ' ')
            ->headline()
            ->replace(' ', '')
            ->toString();

        return Str::endsWith($class, 'Report') ? $class : $class.'Report';
    }

    private function reportKey(string $class): string
    {
        return Str::of($class)
            ->beforeLast('Report')
            ->snake()
            ->toString();
    }

    private function reportLabel(string $class): string
    {
        return Str::of($class)
            ->beforeLast('Report')
            ->headline()
            ->toString();
    }

    private function stub(string $class): string
    {
        $key = $this->reportKey($class);
        $label = $this->reportLabel($class);

        return <<<PHP
<?php

namespace App\Reports\Definitions;

use App\Reports\ReportDefinition;

class {$class}
{
    public static function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: '{$key}',
            label: '{$label}',
            description: '',
            filters: [],
            columns: [],
        );
    }
}
PHP;
    }
}

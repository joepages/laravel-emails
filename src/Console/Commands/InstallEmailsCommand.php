<?php

declare(strict_types=1);

namespace Emails\Console\Commands;

use Emails\Services\TenancyResolver;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallEmailsCommand extends Command
{
    protected $signature = 'emails:install
                            {--force : Overwrite existing files}
                            {--skip-migrations : Skip publishing migrations}';

    protected $description = 'Install the Emails package';

    public function __construct(
        private Filesystem $files,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Installing Emails package...');
        $this->newLine();

        // 1. Publish config
        $this->publishConfig();

        // 2. Publish migrations (unless skipped)
        if (! $this->option('skip-migrations')) {
            $this->publishMigrations();
        }

        $this->newLine();
        $this->info('Emails package installed successfully!');
        $this->newLine();

        $this->displayNextSteps();

        return self::SUCCESS;
    }

    protected function publishConfig(): void
    {
        $source = __DIR__ . '/../../Config/emails.php';
        $destination = config_path('emails.php');

        if ($this->files->exists($destination) && ! $this->option('force')) {
            $this->warn('  Config file already exists. Use --force to overwrite.');

            return;
        }

        $this->files->copy($source, $destination);
        $this->info('  ✓ Published: config/emails.php');
    }

    protected function publishMigrations(): void
    {
        $source = __DIR__ . '/../../Database/Migrations';
        $tenancyResolver = new TenancyResolver;
        $isMultiTenant = $tenancyResolver->isMultiTenant();

        // Determine destination based on app type
        $destination = $isMultiTenant
            ? database_path('migrations/tenant')
            : database_path('migrations');

        $label = $isMultiTenant ? 'tenant migrations' : 'migrations';

        $this->info("  Publishing {$label} to: {$destination}");
        $this->publishMigrationsTo($source, $destination);
    }

    protected function publishMigrationsTo(string $source, string $destination): void
    {
        if (! $this->files->isDirectory($source)) {
            $this->warn('  Source migrations directory not found.');

            return;
        }

        if (! $this->files->isDirectory($destination)) {
            $this->files->makeDirectory($destination, 0755, true);
        }

        foreach ($this->files->files($source) as $file) {
            $destinationPath = $destination . '/' . $file->getFilename();

            if ($this->files->exists($destinationPath) && ! $this->option('force')) {
                $this->warn("    Skipping {$file->getFilename()} (already exists)");

                continue;
            }

            $this->files->copy($file->getPathname(), $destinationPath);
            $this->info("    ✓ {$file->getFilename()}");
        }
    }

    protected function displayNextSteps(): void
    {
        $this->components->info('Next steps:');
        $this->newLine();

        $this->line('  1. Review and update <comment>config/emails.php</comment>');
        $this->newLine();

        $this->line('  2. Run migrations:');
        $this->line('     <comment>php artisan migrate</comment>');
        $this->newLine();

        $this->line('  3. Add the <comment>HasEmails</comment> trait to your models:');
        $this->line('     <comment>use Emails\Concerns\HasEmails;</comment>');
        $this->newLine();

        $this->line('  4. Add the <comment>ManagesEmails</comment> trait to your controllers:');
        $this->line('     <comment>use Emails\Concerns\ManagesEmails;</comment>');
        $this->newLine();

        $this->line('  5. Register routes using the macro:');
        $this->line("     <comment>Route::emailRoutes('facilities', FacilityController::class);</comment>");
    }
}

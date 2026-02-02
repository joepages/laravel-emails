<?php

declare(strict_types=1);

namespace Emails;

use Emails\Contracts\EmailRepositoryInterface;
use Emails\Contracts\EmailServiceInterface;
use Emails\Repositories\EmailRepository;
use Emails\Services\EmailService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class EmailsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/emails.php',
            'emails'
        );

        // Repository
        $this->app->bind(EmailRepositoryInterface::class, EmailRepository::class);

        // Service
        $this->app->singleton(EmailServiceInterface::class, EmailService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        $this->registerRouteMacro();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/Config/emails.php' => config_path('emails.php'),
            ], 'emails-config');

            $this->commands([
                Console\Commands\InstallEmailsCommand::class,
            ]);
        }
    }

    protected function registerRouteMacro(): void
    {
        Route::macro('emailRoutes', function (string $prefix, string $controller) {
            $singular = Str::singular($prefix);

            Route::prefix("{$prefix}/{{$singular}}")->group(function () use ($controller) {
                Route::get('/emails', [$controller, 'listEmails']);
                Route::post('/emails', [$controller, 'storeEmail']);
                Route::put('/emails/{email}', [$controller, 'updateEmail']);
                Route::delete('/emails/{email}', [$controller, 'deleteEmail']);
            });
        });
    }
}

<?php

declare(strict_types=1);

namespace Emails\Tests;

use Emails\EmailsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase as LaravelTestCase;

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ClassFileName.NoMatch

// When running inside the main Laravel app, use its TestCase.
// When extracted as a standalone package, this will use Orchestra Testbench.
// @phpstan-ignore-next-line
if (class_exists(LaravelTestCase::class)) {
    abstract class TestCaseBase extends LaravelTestCase {}
} else {
    // @codeCoverageIgnoreStart
    /** @noRector \Rector\CodingStyle\Rector\Stmt\UseClassKeywordForClassNameResolutionRector */
    abstract class TestCaseBase extends \Orchestra\Testbench\TestCase {} // @codingStandardsIgnoreLine
    // @codeCoverageIgnoreEnd
}

/**
 * Base test case for Emails package tests.
 *
 * This class automatically adapts to run either:
 * 1. Inside the main Laravel application (uses Tests\TestCase)
 * 2. As a standalone package (uses Orchestra\Testbench\TestCase)
 */
abstract class TestCase extends TestCaseBase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'sync']);
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            EmailsServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../src/Database/Migrations');
        $this->loadMigrationsFrom(__DIR__ . '/Helpers/migrations');
    }
}

<?php

declare(strict_types=1);

namespace Emails\Tests;

use Emails\EmailsServiceProvider;
use Tests\TestCase as LaravelTestCase;

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ClassFileName.NoMatch

// When running inside the main Laravel app, use its TestCase.
// When extracted as a standalone package, this will use Orchestra Testbench.
// @phpstan-ignore-next-line
if (class_exists(LaravelTestCase::class)) {
    abstract class UnitTestCaseBase extends LaravelTestCase {}
} else {
    // @codeCoverageIgnoreStart
    /** @noRector \Rector\CodingStyle\Rector\Stmt\UseClassKeywordForClassNameResolutionRector */
    abstract class UnitTestCaseBase extends \Orchestra\Testbench\TestCase {} // @codingStandardsIgnoreLine
    // @codeCoverageIgnoreEnd
}

/**
 * Base test case for pure unit tests that don't require database.
 */
abstract class UnitTestCase extends UnitTestCaseBase
{
    protected function setUp(): void
    {
        // When running inside the main Laravel app, register the service provider
        // before parent::setUp() since Orchestra Testbench lifecycle methods
        // (getPackageProviders, defineEnvironment) don't exist in Laravel's base TestCase.
        if (class_exists(LaravelTestCase::class)) {
            $this->refreshApplication();
            $this->app->register(EmailsServiceProvider::class);
        }

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
        $app['config']->set('emails.types', ['personal', 'work', 'billing', 'other']);
        $app['config']->set('emails.default_type', 'personal');
    }
}

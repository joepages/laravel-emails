<?php

declare(strict_types=1);

namespace Emails\Tests;

use Emails\EmailsServiceProvider;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\CreatesApplication;

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ClassFileName.NoMatch

// @phpstan-ignore-next-line
if (trait_exists(CreatesApplication::class)) {
    abstract class UnitTestCaseBase extends BaseTestCase
    {
        use CreatesApplication;
    }
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

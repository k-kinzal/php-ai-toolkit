<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Legacy;

use function array_keys;
use function dirname;
use function getcwd;
use function iterator_to_array;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function putenv;

use Toolkit\Doctest\Configuration\Configuration;
use Toolkit\Doctest\Configuration\ConfigurationLoader;
use Toolkit\Doctest\Legacy\LegacyDoctestSuite;
use Toolkit\Doctest\TestCase\Legacy\LegacyDoctestRunner;

/**
 * @covers \Toolkit\Doctest\Legacy\LegacyDoctestSuite
 * @uses \Toolkit\Doctest\Configuration\Configuration
 * @uses \Toolkit\Doctest\Configuration\ConfigurationLoader
 * @uses \Toolkit\Doctest\TestCase\Legacy\LegacyDoctestRunner
 * @medium
 */
#[CoversClass(LegacyDoctestSuite::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationLoader::class)]
#[UsesClass(LegacyDoctestRunner::class)]
#[Medium]
final class LegacyDoctestSuiteTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        putenv('DOCTEST_DIRECTORIES');
        putenv('DOCTEST_FILES');
        putenv('DOCTEST_EXCLUDE');
        putenv('DOCTEST_BOOTSTRAP');
        putenv('DOCTEST_ENABLED');
    }

    #[Override]
    protected function tearDown(): void
    {
        putenv('DOCTEST_DIRECTORIES');
        putenv('DOCTEST_FILES');
        putenv('DOCTEST_EXCLUDE');
        putenv('DOCTEST_BOOTSTRAP');
        putenv('DOCTEST_ENABLED');
        parent::tearDown();
    }

    public function testConfigureReadsTheVariablesPhpUnitXmlExports(): void
    {
        putenv('DOCTEST_DIRECTORIES=src, lib');
        putenv('DOCTEST_EXCLUDE=*/Nested/*');
        $workingDirectory = (string) getcwd();

        $config = LegacyDoctestSuite::configure();

        self::assertSame([$workingDirectory . '/src', $workingDirectory . '/lib'], $config->getDirectories());
        self::assertSame(['*/Nested/*'], $config->getExcludePatterns());
        self::assertTrue($config->isEnabled());
    }

    public function testConfigureIsEmptyWhenTheEnvironmentSetsNothing(): void
    {
        $config = LegacyDoctestSuite::configure();

        self::assertFalse($config->hasSources());
        self::assertSame([], $config->getExcludePatterns());
        self::assertNull($config->getBootstrap());
        self::assertTrue($config->isEnabled());
    }

    public function testConfigureIsEmptyWhenDoctestIsSwitchedOff(): void
    {
        putenv('DOCTEST_DIRECTORIES=src');
        putenv('DOCTEST_ENABLED=false');

        $config = LegacyDoctestSuite::configure();

        self::assertFalse($config->hasSources());
    }

    public function testDoctestProviderNamesEveryExampleTheEnvironmentSelects(): void
    {
        putenv('DOCTEST_DIRECTORIES=' . dirname(__DIR__, 4) . '/tests/Fixture/Doctest/project/src');
        putenv('DOCTEST_EXCLUDE=*/Nested/*');

        $provided = iterator_to_array(LegacyDoctestSuite::doctestProvider());

        self::assertSame(
            [
                'Calculator example #1: Building a calculator',
                'Calculator::add() example #1: Adding two numbers',
                'Calculator::add() example #2: Adding across several lines',
                'Calculator::divide() example #1: Refusing to divide by zero',
                'Calculator::printSum() example #1: Printing a sum',
            ],
            array_keys($provided),
        );
    }
}

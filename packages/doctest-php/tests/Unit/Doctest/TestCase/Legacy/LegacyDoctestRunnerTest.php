<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\TestCase\Legacy;

use function array_keys;
use function iterator_to_array;

use Override;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\TestCase;
use Toolkit\Doctest\TestCase\Legacy\LegacyDoctestRunner;

/**
 * @covers \Toolkit\Doctest\TestCase\Legacy\LegacyDoctestRunner
 * @medium
 */
#[CoversClass(LegacyDoctestRunner::class)]
#[Medium]
final class LegacyDoctestRunnerTest extends TestCase
{
    public function testConfigureIsWhatTheSuiteStates(): void
    {
        $suite = new class ('testDocblockExample') extends LegacyDoctestRunner {
            /**
             * Returns the configuration selecting the fixture project sources.
             */
            #[Override]
            public static function configure(): \Toolkit\Doctest\Configuration\Configuration
            {
                return new \Toolkit\Doctest\Configuration\Configuration(directories: [dirname(__DIR__, 5) . '/tests/Doctest/project/src'], excludePatterns: ['*/Nested/*']);
            }
        };
        self::assertStringEndsWith('tests/Doctest/project/src', $suite::configure()->getDirectories()[0]);
    }

    public function testDoctestProviderNamesEveryExampleAfterItsTarget(): void
    {
        $suite = new class ('testDocblockExample') extends LegacyDoctestRunner {
            /**
             * Returns the configuration selecting the fixture project sources.
             */
            #[Override]
            public static function configure(): \Toolkit\Doctest\Configuration\Configuration
            {
                return new \Toolkit\Doctest\Configuration\Configuration(directories: [dirname(__DIR__, 5) . '/tests/Doctest/project/src'], excludePatterns: ['*/Nested/*']);
            }
        };
        $provided = iterator_to_array($suite::doctestProvider());

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

    public function testDoctestProviderReturnsSkipCaseWhenNoExamplesExist(): void
    {
        $emptySuite = new class ('testDocblockExample') extends LegacyDoctestRunner {
            /**
             * Returns a configuration that intentionally discovers no examples.
             */
            #[Override]
            public static function configure(): \Toolkit\Doctest\Configuration\Configuration
            {
                return new \Toolkit\Doctest\Configuration\Configuration(directories: []);
            }
        };
        self::assertSame(
            ['No doctest examples found' => [null]],
            iterator_to_array($emptySuite::doctestProvider()),
        );
    }

    public function testTestDocblockExamplePassesWhenNoExamplesExist(): void
    {
        $emptySuite = new class ('testDocblockExample') extends LegacyDoctestRunner {
            /**
             * Returns a configuration that intentionally discovers no examples.
             */
            #[Override]
            public static function configure(): \Toolkit\Doctest\Configuration\Configuration
            {
                return new \Toolkit\Doctest\Configuration\Configuration(directories: []);
            }
        };
        $this->expectNotToPerformAssertions();
        $case = $emptySuite;

        $case->testDocblockExample(null);
    }

    public function testTestDocblockExamplePassesForAnExampleThatHolds(): void
    {
        $suite = new class ('testDocblockExample') extends LegacyDoctestRunner {
            /**
             * Returns the configuration selecting the fixture project sources.
             */
            #[Override]
            public static function configure(): \Toolkit\Doctest\Configuration\Configuration
            {
                return new \Toolkit\Doctest\Configuration\Configuration(directories: [dirname(__DIR__, 5) . '/tests/Doctest/project/src'], excludePatterns: ['*/Nested/*']);
            }
        };
        $provided = iterator_to_array($suite::doctestProvider());
        $case = $suite;
        $before = Assert::getCount();

        $case->testDocblockExample($provided['Calculator::divide() example #1: Refusing to divide by zero'][0]);

        self::assertSame($before + 1, Assert::getCount());
    }
}

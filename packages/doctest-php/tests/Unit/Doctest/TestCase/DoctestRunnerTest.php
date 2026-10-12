<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\TestCase;

use function array_keys;
use function iterator_to_array;

use Override;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Toolkit\Doctest\Parser\Example;
use Toolkit\Doctest\TestCase\DoctestRunner;

/**
 * @covers \Toolkit\Doctest\TestCase\DoctestRunner
 * @medium
 */
#[CoversClass(DoctestRunner::class)]
#[Medium]
final class DoctestRunnerTest extends TestCase
{
    public function testConfigureIsWhatTheSuiteStates(): void
    {
        $suite = new class ('testDocblockExample') extends DoctestRunner {
            /**
             * Returns the configuration selecting the fixture project sources.
             */
            #[Override]
            public static function configure(): \Toolkit\Doctest\Configuration\Configuration
            {
                return new \Toolkit\Doctest\Configuration\Configuration(directories: [dirname(__DIR__, 4) . '/tests/Doctest/project/src'], excludePatterns: ['*/Nested/*']);
            }
        };
        $config = $suite::configure();

        self::assertStringEndsWith('tests/Doctest/project/src', $config->getDirectories()[0]);
        self::assertSame(['*/Nested/*'], $config->getExcludePatterns());
    }

    public function testDoctestProviderNamesEveryExampleAfterItsTarget(): void
    {
        $suite = new class ('testDocblockExample') extends DoctestRunner {
            /**
             * Returns the configuration selecting the fixture project sources.
             */
            #[Override]
            public static function configure(): \Toolkit\Doctest\Configuration\Configuration
            {
                return new \Toolkit\Doctest\Configuration\Configuration(directories: [dirname(__DIR__, 4) . '/tests/Doctest/project/src'], excludePatterns: ['*/Nested/*']);
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
        $example = $provided['Calculator example #1: Building a calculator'][0];
        self::assertInstanceOf(Example::class, $example);
        self::assertSame('Calculator example #1: Building a calculator', $example->getName());
    }

    public function testDoctestProviderReturnsSkipCaseWhenNoExamplesExist(): void
    {
        $emptySuite = new class ('testDocblockExample') extends DoctestRunner {
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
        $emptySuite = new class ('testDocblockExample') extends DoctestRunner {
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
        $suite = new class ('testDocblockExample') extends DoctestRunner {
            /**
             * Returns the configuration selecting the fixture project sources.
             */
            #[Override]
            public static function configure(): \Toolkit\Doctest\Configuration\Configuration
            {
                return new \Toolkit\Doctest\Configuration\Configuration(directories: [dirname(__DIR__, 4) . '/tests/Doctest/project/src'], excludePatterns: ['*/Nested/*']);
            }
        };
        $provided = iterator_to_array($suite::doctestProvider());
        $case = $suite;
        $before = Assert::getCount();

        $case->testDocblockExample($provided['Calculator::add() example #1: Adding two numbers'][0]);

        self::assertSame($before + 1, Assert::getCount());
    }

    public function testTestDocblockExampleFailsWithTheDoctestReport(): void
    {
        $suite = new class ('testDocblockExample') extends DoctestRunner {
            /**
             * Returns the configuration selecting the fixture project sources.
             */
            #[Override]
            public static function configure(): \Toolkit\Doctest\Configuration\Configuration
            {
                return new \Toolkit\Doctest\Configuration\Configuration(directories: [dirname(__DIR__, 4) . '/tests/Doctest/project/src'], excludePatterns: ['*/Nested/*']);
            }
        };
        $target = new \Toolkit\Doctest\Scanner\Target(
            \Toolkit\Doctest\Scanner\TargetKind::CLASS_LIKE,
            (string) realpath(__DIR__ . '/../../../../tests/Doctest/project/src/Calculator.php'),
            '/** */',
            'Calculator',
            12,
        );
        $case = $suite;

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Values do not match');

        $case->testDocblockExample(new Example('1 + 1 // => 3', $target, 14, 0));
    }
}

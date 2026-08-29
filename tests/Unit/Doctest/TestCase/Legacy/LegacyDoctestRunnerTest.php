<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\TestCase\Legacy;

use function array_keys;
use function iterator_to_array;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\TestCase;
use Tests\Fixture\Doctest\LegacyFixtureDoctestSuite;
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
        self::assertStringEndsWith('Fixture/Doctest/project/src', LegacyFixtureDoctestSuite::configure()->getDirectories()[0]);
    }

    public function testDoctestProviderNamesEveryExampleAfterItsTarget(): void
    {
        $provided = iterator_to_array(LegacyFixtureDoctestSuite::doctestProvider());

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

    public function testTestDocblockExamplePassesForAnExampleThatHolds(): void
    {
        $provided = iterator_to_array(LegacyFixtureDoctestSuite::doctestProvider());
        $case = new LegacyFixtureDoctestSuite('testDocblockExample');
        $before = Assert::getCount();

        $case->testDocblockExample($provided['Calculator::divide() example #1: Refusing to divide by zero'][0]);

        self::assertSame($before + 1, Assert::getCount());
    }
}

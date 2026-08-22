<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\Doctest\Legacy;

use PhpAiToolkit\PhpUnit\Doctest\Legacy\LegacyDoctestTestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\TestCase;
use Tests\Fixture\Doctest\LegacyFixtureDoctestTestCase;

#[CoversClass(LegacyDoctestTestCase::class)]
#[Medium]
final class LegacyDoctestTestCaseTest extends TestCase
{
    public function testDoctestRootDefaultsToTheDirectoryPhpUnitRunsFrom(): void
    {
        self::assertSame(getcwd(), LegacyDoctestTestCase::doctestRoot());
    }

    public function testDoctestPathsDefaultToTheSourceDirectory(): void
    {
        self::assertSame(['src'], LegacyDoctestTestCase::doctestPaths());
    }

    public function testDoctestExcludesAreEmptyUntilASuiteNarrowsThem(): void
    {
        self::assertSame([], LegacyDoctestTestCase::doctestExcludes());
        self::assertSame(['src/Nested/*'], LegacyFixtureDoctestTestCase::doctestExcludes());
    }

    public function testDoctestBootstrapIsAbsentForAnAutoloadedProject(): void
    {
        self::assertNull(LegacyDoctestTestCase::doctestBootstrap());
    }

    public function testDoctestConfigAssemblesTheOverriddenSettings(): void
    {
        $config = LegacyFixtureDoctestTestCase::doctestConfig();

        self::assertStringEndsWith('Fixture/Doctest/project', $config->root);
        self::assertSame(['src/Nested/*'], $config->exclude);
    }

    public function testDoctestIndexIsBuiltOncePerSuite(): void
    {
        self::assertSame(LegacyFixtureDoctestTestCase::doctestIndex(), LegacyFixtureDoctestTestCase::doctestIndex());
    }

    public function testDoctestExampleProviderNamesEveryExampleByItsIdentifier(): void
    {
        $provided = iterator_to_array(LegacyFixtureDoctestTestCase::doctestExampleProvider());

        self::assertCount(6, $provided);
        self::assertSame(
            ['Tests\Fixture\Doctest\Project\Calculator::divide()#1'],
            $provided['Calculator::divide() example #1: Refusing to divide by zero [Tests\Fixture\Doctest\Project\Calculator::divide()#1]'],
        );
    }

    public function testTestDocblockExamplePassesForADocumentedExampleThatHolds(): void
    {
        $case = new LegacyFixtureDoctestTestCase('testDocblockExample');
        $before = Assert::getCount();
        $case->testDocblockExample('Tests\Fixture\Doctest\Project\Calculator::divide()#1');

        self::assertSame($before + 1, Assert::getCount());
    }
}

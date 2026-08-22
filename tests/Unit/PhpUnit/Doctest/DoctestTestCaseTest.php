<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\Doctest;

use PhpAiToolkit\PhpUnit\Doctest\DoctestTestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Tests\Fixture\Doctest\FailingFixtureDoctestTestCase;
use Tests\Fixture\Doctest\FixtureDoctestTestCase;

#[CoversClass(DoctestTestCase::class)]
#[Medium]
final class DoctestTestCaseTest extends TestCase
{
    public function testDoctestRootDefaultsToTheDirectoryPhpUnitRunsFrom(): void
    {
        self::assertSame(getcwd(), DoctestTestCase::doctestRoot());
    }

    public function testDoctestPathsDefaultToTheSourceDirectory(): void
    {
        self::assertSame(['src'], DoctestTestCase::doctestPaths());
    }

    public function testDoctestExcludesAreEmptyUntilASuiteNarrowsThem(): void
    {
        self::assertSame([], DoctestTestCase::doctestExcludes());
        self::assertSame(['src/Nested/*'], FixtureDoctestTestCase::doctestExcludes());
    }

    public function testDoctestBootstrapIsAbsentForAnAutoloadedProject(): void
    {
        self::assertNull(DoctestTestCase::doctestBootstrap());
    }

    public function testDoctestConfigAssemblesTheOverriddenSettings(): void
    {
        $config = FixtureDoctestTestCase::doctestConfig();

        self::assertStringEndsWith('Fixture/Doctest/project', $config->root);
        self::assertSame(['src'], $config->paths);
        self::assertSame(['src/Nested/*'], $config->exclude);
        self::assertNull($config->bootstrap);
    }

    public function testDoctestIndexIsBuiltOncePerSuite(): void
    {
        self::assertSame(FixtureDoctestTestCase::doctestIndex(), FixtureDoctestTestCase::doctestIndex());
        self::assertNotSame(FixtureDoctestTestCase::doctestIndex(), FailingFixtureDoctestTestCase::doctestIndex());
    }

    public function testDoctestExampleProviderNamesEveryExampleByItsIdentifier(): void
    {
        $provided = iterator_to_array(FixtureDoctestTestCase::doctestExampleProvider());

        self::assertCount(6, $provided);
        self::assertSame(
            ['Tests\Fixture\Doctest\Project\Calculator::add()#1'],
            $provided['Calculator::add() example #1: Adding two numbers [Tests\Fixture\Doctest\Project\Calculator::add()#1]'],
        );
    }

    public function testTestDocblockExamplePassesForADocumentedExampleThatHolds(): void
    {
        $case = new FixtureDoctestTestCase('testDocblockExample');
        $before = Assert::getCount();
        $case->testDocblockExample('Tests\Fixture\Doctest\Project\Calculator::add()#1');

        self::assertSame($before + 1, Assert::getCount());
    }

    public function testTestDocblockExampleFailsWithTheReportAndTheCommandThatRerunsIt(): void
    {
        $case = new FailingFixtureDoctestTestCase('testDocblockExample');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("vendor/bin/phpunit --filter '/Tests\\\\Fixture\\\\Doctest\\\\Failing\\\\Broken\\#1/'");

        $case->testDocblockExample('Tests\Fixture\Doctest\Failing\Broken#1');
    }
}

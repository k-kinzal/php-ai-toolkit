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
    public function testDoctestConfigPathDefaultsToTheProjectConfiguration(): void
    {
        self::assertSame('doctest.yaml', DoctestTestCase::doctestConfigPath());
        self::assertStringEndsWith('Fixture/Doctest/project/doctest.yaml', FixtureDoctestTestCase::doctestConfigPath());
    }

    public function testDoctestIndexIsBuiltForTheConfiguredPath(): void
    {
        self::assertSame(FixtureDoctestTestCase::doctestConfigPath(), FixtureDoctestTestCase::doctestIndex()->configPath());
        self::assertSame(FixtureDoctestTestCase::doctestIndex(), FixtureDoctestTestCase::doctestIndex());
    }

    public function testDoctestExampleProviderNamesEveryExampleByItsIdentifier(): void
    {
        $provided = iterator_to_array(FixtureDoctestTestCase::doctestExampleProvider());

        self::assertCount(6, $provided);
        self::assertArrayHasKey(
            'Calculator::add() example #1: Adding two numbers [Tests\Fixture\Doctest\Project\Calculator::add()#1]',
            $provided,
        );
        self::assertSame(['Tests\Fixture\Doctest\Project\Calculator::add()#1'], $provided['Calculator::add() example #1: Adding two numbers [Tests\Fixture\Doctest\Project\Calculator::add()#1]']);
    }

    public function testTestDocblockExamplePassesForADocumentedExampleThatHolds(): void
    {
        $case = new FixtureDoctestTestCase('testDocblockExample');
        $before = Assert::getCount();
        $case->testDocblockExample('Tests\Fixture\Doctest\Project\Calculator::add()#1');

        self::assertSame($before + 1, Assert::getCount());
    }

    public function testTestDocblockExampleFailsWithTheDoctestReportForABrokenExample(): void
    {
        $case = new FailingFixtureDoctestTestCase('testDocblockExample');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Documented example Tests\Fixture\Doctest\Failing\Broken#1 does not hold.');

        $case->testDocblockExample('Tests\Fixture\Doctest\Failing\Broken#1');
    }
}

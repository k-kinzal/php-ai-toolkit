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
    public function testDoctestConfigPathDefaultsToTheProjectConfiguration(): void
    {
        self::assertSame('doctest.yaml', LegacyDoctestTestCase::doctestConfigPath());
        self::assertStringEndsWith('Fixture/Doctest/project/doctest.yaml', LegacyFixtureDoctestTestCase::doctestConfigPath());
    }

    public function testDoctestIndexIsBuiltForTheConfiguredPath(): void
    {
        self::assertSame(LegacyFixtureDoctestTestCase::doctestConfigPath(), LegacyFixtureDoctestTestCase::doctestIndex()->configPath());
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

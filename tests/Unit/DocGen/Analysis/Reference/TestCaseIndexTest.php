<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Reference;

use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageIndex;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\MethodDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCase as ReferenceTestCase;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\Usage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestCaseIndex::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(CoverageIndex::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(ReferenceTestCase::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(Usage::class)]
final class TestCaseIndexTest extends TestCase
{
    public function testBuildMergesCoverageAndCallEvidenceIntoOneTestCase(): void
    {
        $greeter = new ClassLikeDoc('Demo\Greeter', 'Greeter', 'Demo', 'class', 'demo/app', 'src/Greeter.php', 5, 20, false, false, [], [], [], [], [], [new MethodDoc('greet', 'public', false, false, false, [], new TypeSignature('void', null), null, 10, 14)], [], null, null, [], false);
        $greeterTest = new ClassLikeDoc('Tests\Unit\GreeterTest', 'GreeterTest', 'Tests\Unit', 'class', 'demo/app', 'tests/Unit/GreeterTest.php', 8, 30, false, false, [], [], [], [], [], [new MethodDoc('testGreet', 'public', false, false, false, [], new TypeSignature('void', null), null, 12, 18)], [], null, null, [], true);
        $coverage = new CoverageIndex();
        $coverage->addLine('src/Greeter.php', 11, ['Tests\Unit\GreeterTest::testGreet']);

        $index = new TestCaseIndex();
        $index->build(
            [new Usage('Demo\Greeter', 'greet', 'method-call', 'Tests\Unit\GreeterTest', 'testGreet', 'tests/Unit/GreeterTest.php', 15, true)],
            [$greeter, $greeterTest],
            $coverage,
        );

        $cases = $index->forMember('Demo\Greeter', 'greet');

        self::assertCount(1, $cases);
        self::assertSame('Tests\Unit\GreeterTest', $cases[0]->testClass);
        self::assertSame('testGreet', $cases[0]->testMethod);
        self::assertSame('tests/Unit/GreeterTest.php', $cases[0]->file);
        self::assertSame(15, $cases[0]->line);
        self::assertSame('coverage+call', $cases[0]->origin);
    }

    public function testBuildKeepsCallEvidenceForMethodsWithoutLineCoverage(): void
    {
        $greeter = new ClassLikeDoc('Demo\Greeter', 'Greeter', 'Demo', 'class', 'demo/app', 'src/Greeter.php', 5, 20, false, false, [], [], [], [], [], [new MethodDoc('greet', 'public', false, false, false, [], new TypeSignature('void', null), null, 10, 14)], [], null, null, [], false);
        $coverage = new CoverageIndex();
        $coverage->addLine('src/Greeter.php', 18, ['Tests\Unit\OtherTest::testOther']);

        $index = new TestCaseIndex();
        $index->build(
            [new Usage('Demo\Greeter', 'greet', 'method-call', 'Tests\Unit\GreeterTest', 'testGreet', 'tests/Unit/GreeterTest.php', 15, true)],
            [$greeter],
            $coverage,
        );

        $cases = $index->forMember('Demo\Greeter', 'greet');

        self::assertCount(1, $cases);
        self::assertSame('Tests\Unit\GreeterTest', $cases[0]->testClass);
        self::assertSame('call', $cases[0]->origin);
        self::assertCount(2, $index->forType('Demo\Greeter'));
    }

    public function testBuildWithoutCoverageUsesCallEvidenceOnly(): void
    {
        $index = new TestCaseIndex();
        $index->build([
            new Usage('Demo\Greeter', 'greet', 'method-call', 'Tests\Unit\GreeterTest', 'testGreet', 'tests/Unit/GreeterTest.php', 15, true),
            new Usage('Demo\Greeter', null, 'new', 'Demo\App', 'run', 'src/App.php', 7, false),
        ]);

        $cases = $index->forType('Demo\Greeter');

        self::assertCount(1, $cases);
        self::assertSame('call', $cases[0]->origin);
    }

    public function testRegisterTestSymbolSuppliesFileAndLineForCoverageEvidence(): void
    {
        $greeterTest = new ClassLikeDoc('Tests\Unit\GreeterTest', 'GreeterTest', 'Tests\Unit', 'class', 'demo/app', 'tests/Unit/GreeterTest.php', 8, 30, false, false, [], [], [], [], [], [new MethodDoc('testGreet', 'public', false, false, false, [], new TypeSignature('void', null), null, 12, 18)], [], null, null, [], true);
        $coverage = new CoverageIndex();
        $coverage->addLine('src/Greeter.php', 11, ['Tests\Unit\GreeterTest::testGreet']);

        $index = new TestCaseIndex();
        $index->registerTestSymbol($greeterTest);
        $index->addCoverageRange($coverage, 'Demo\Greeter', 'greet', 'src/Greeter.php', 10, 14);

        $cases = $index->forMember('Demo\Greeter', 'greet');

        self::assertSame('tests/Unit/GreeterTest.php', $cases[0]->file);
        self::assertSame(12, $cases[0]->line);
    }

    public function testRegisterTestSymbolIgnoresProductionClasses(): void
    {
        $greeter = new ClassLikeDoc('Demo\Greeter', 'Greeter', 'Demo', 'class', 'demo/app', 'src/Greeter.php', 5, 20, false, false, [], [], [], [], [], [new MethodDoc('greet', 'public', false, false, false, [], new TypeSignature('void', null), null, 10, 14)], [], null, null, [], false);
        $coverage = new CoverageIndex();
        $coverage->addLine('src/Other.php', 4, ['Demo\Greeter::greet']);

        $index = new TestCaseIndex();
        $index->registerTestSymbol($greeter);
        $index->addCoverageRange($coverage, 'Demo\Other', null, 'src/Other.php', 1, 9);

        $cases = $index->forType('Demo\Other');

        self::assertNull($cases[0]->file);
        self::assertNull($cases[0]->line);
    }

    public function testAddCallRecordsTheTestForBothTheClassAndTheMember(): void
    {
        $index = new TestCaseIndex();
        $index->addCall(new Usage('Demo\Greeter', 'greet', 'method-call', 'Tests\Unit\GreeterTest', 'testGreet', 'tests/Unit/GreeterTest.php', 15, true));

        self::assertCount(1, $index->forType('Demo\Greeter'));
        self::assertCount(1, $index->forMember('Demo\Greeter', 'greet'));
        self::assertSame(15, $index->forMember('Demo\Greeter', 'greet')[0]->line);
    }

    public function testAddCallIgnoresProductionUsagesAndUsagesWithoutOrigin(): void
    {
        $index = new TestCaseIndex();
        $index->addCall(new Usage('Demo\Greeter', null, 'new', 'Demo\App', 'run', 'src/App.php', 7, false));
        $index->addCall(new Usage('Demo\Greeter', null, 'new', null, null, 'tests/Unit/GreeterTest.php', 9, true));

        self::assertSame([], $index->forType('Demo\Greeter'));
    }

    public function testAddCoverageRecordsTheClassRangeAndEveryMethodRange(): void
    {
        $greeter = new ClassLikeDoc('Demo\Greeter', 'Greeter', 'Demo', 'class', 'demo/app', 'src/Greeter.php', 5, 20, false, false, [], [], [], [], [], [new MethodDoc('greet', 'public', false, false, false, [], new TypeSignature('void', null), null, 10, 14)], [], null, null, [], false);
        $coverage = new CoverageIndex();
        $coverage->addLine('src/Greeter.php', 11, ['Tests\Unit\GreeterTest::testGreet']);
        $coverage->addLine('src/Greeter.php', 18, ['Tests\Unit\GreeterTest::testFarewell']);

        $index = new TestCaseIndex();
        $index->addCoverage($coverage, $greeter);

        self::assertCount(2, $index->forType('Demo\Greeter'));
        self::assertCount(1, $index->forMember('Demo\Greeter', 'greet'));
        self::assertSame('testGreet', $index->forMember('Demo\Greeter', 'greet')[0]->testMethod);
    }

    public function testAddCoverageSkipsDevClasses(): void
    {
        $greeterTest = new ClassLikeDoc('Tests\Unit\GreeterTest', 'GreeterTest', 'Tests\Unit', 'class', 'demo/app', 'tests/Unit/GreeterTest.php', 8, 30, false, false, [], [], [], [], [], [], [], null, null, [], true);
        $coverage = new CoverageIndex();
        $coverage->addLine('tests/Unit/GreeterTest.php', 12, ['Tests\Unit\GreeterTest::testGreet']);

        $index = new TestCaseIndex();
        $index->addCoverage($coverage, $greeterTest);

        self::assertSame([], $index->forType('Tests\Unit\GreeterTest'));
    }

    public function testAddCoverageRangeAcceptsIdentifiersWithoutAMethodName(): void
    {
        $coverage = new CoverageIndex();
        $coverage->addLine('src/Greeter.php', 11, ['Tests\Unit\GreeterTest', '']);

        $index = new TestCaseIndex();
        $index->addCoverageRange($coverage, 'Demo\Greeter', null, 'src/Greeter.php', 5, 20);

        $cases = $index->forType('Demo\Greeter');

        self::assertCount(1, $cases);
        self::assertSame('Tests\Unit\GreeterTest', $cases[0]->testClass);
        self::assertNull($cases[0]->testMethod);
    }

    public function testRecordStoresOneTestCaseUnderTheClassAndTheMemberKey(): void
    {
        $index = new TestCaseIndex();
        $index->record('Demo\Greeter', 'greet', new ReferenceTestCase('Tests\Unit\GreeterTest', 'testGreet', 'tests/Unit/GreeterTest.php', 15, ReferenceTestCase::ORIGIN_CALL));
        $index->record('Demo\Greeter', null, new ReferenceTestCase('Tests\Unit\GreeterTest', 'testGreet', 'tests/Unit/GreeterTest.php', 15, ReferenceTestCase::ORIGIN_COVERAGE));

        self::assertCount(1, $index->forType('DEMO\GREETER'));
        self::assertSame('coverage+call', $index->forType('Demo\Greeter')[0]->origin);
        self::assertSame('call', $index->forMember('Demo\Greeter', 'GREET')[0]->origin);
    }

    public function testMergeKeepsTheKnownPositionAndCombinesOrigins(): void
    {
        $known = new ReferenceTestCase('Tests\Unit\GreeterTest', 'testGreet', 'tests/Unit/GreeterTest.php', 15, ReferenceTestCase::ORIGIN_CALL);
        $found = new ReferenceTestCase('Tests\Unit\GreeterTest', 'testGreet', 'tests/Unit/Other.php', 99, ReferenceTestCase::ORIGIN_COVERAGE);

        $merged = (new TestCaseIndex())->merge($known, $found);

        self::assertSame('tests/Unit/GreeterTest.php', $merged->file);
        self::assertSame(15, $merged->line);
        self::assertSame('coverage+call', $merged->origin);
    }

    public function testMergeFillsMissingPositionsAndKeepsEqualOrigins(): void
    {
        $known = new ReferenceTestCase('Tests\Unit\GreeterTest', null, null, null, ReferenceTestCase::ORIGIN_COVERAGE);
        $found = new ReferenceTestCase('Tests\Unit\GreeterTest', 'testGreet', 'tests/Unit/GreeterTest.php', 15, ReferenceTestCase::ORIGIN_COVERAGE);

        $merged = (new TestCaseIndex())->merge($known, $found);

        self::assertSame('testGreet', $merged->testMethod);
        self::assertSame('tests/Unit/GreeterTest.php', $merged->file);
        self::assertSame(15, $merged->line);
        self::assertSame('coverage', $merged->origin);
    }

    public function testForTypeReturnsEmptyListForUnknownType(): void
    {
        self::assertSame([], (new TestCaseIndex())->forType('Demo\Missing'));
    }

    public function testForMemberReturnsEmptyListForUnknownMember(): void
    {
        self::assertSame([], (new TestCaseIndex())->forMember('Demo\Greeter', 'missing'));
    }

    public function testSortedOrdersByTestClassThenTestMethod(): void
    {
        $first = new ReferenceTestCase('Tests\Unit\AlphaTest', 'testAlpha', null, null, ReferenceTestCase::ORIGIN_CALL);
        $second = new ReferenceTestCase('Tests\Unit\ZuluTest', 'testAlpha', null, null, ReferenceTestCase::ORIGIN_CALL);
        $third = new ReferenceTestCase('Tests\Unit\ZuluTest', 'testZulu', null, null, ReferenceTestCase::ORIGIN_CALL);

        $sorted = (new TestCaseIndex())->sorted(['c' => $third, 'a' => $second, 'b' => $first]);

        self::assertSame([$first, $second, $third], $sorted);
    }

    public function testSortedDropsTheClassLevelEntryOfADetailedTestClass(): void
    {
        $classLevel = new ReferenceTestCase('Tests\Unit\AlphaTest', null, null, null, ReferenceTestCase::ORIGIN_CALL);
        $method = new ReferenceTestCase('Tests\Unit\AlphaTest', 'testAlpha', null, null, ReferenceTestCase::ORIGIN_CALL);
        $lonely = new ReferenceTestCase('Tests\Unit\ZuluTest', null, null, null, ReferenceTestCase::ORIGIN_COVERAGE);

        self::assertSame([$method, $lonely], (new TestCaseIndex())->sorted(['a' => $classLevel, 'b' => $method, 'c' => $lonely]));
    }

    public function testWithoutClassLevelDuplicatesKeepsTheOnlyEvidenceOfAClass(): void
    {
        $classLevel = new ReferenceTestCase('Tests\Unit\AlphaTest', null, null, null, ReferenceTestCase::ORIGIN_CALL);
        $method = new ReferenceTestCase('Tests\Unit\AlphaTest', 'testAlpha', null, null, ReferenceTestCase::ORIGIN_CALL);

        self::assertSame(['a' => $classLevel], (new TestCaseIndex())->withoutClassLevelDuplicates(['a' => $classLevel]));
        self::assertSame(['b' => $method], (new TestCaseIndex())->withoutClassLevelDuplicates(['a' => $classLevel, 'b' => $method]));
    }
}

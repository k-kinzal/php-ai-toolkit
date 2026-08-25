<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Reference;

use function array_keys;

use PhpAiToolkit\DocGen\Analysis\Reference\Usage;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \PhpAiToolkit\DocGen\Analysis\Reference\Usage
 */
#[CoversClass(UsageIndex::class)]
#[UsesClass(Usage::class)]
final class UsageIndexTest extends TestCase
{
    public function testBuildDropsExactDuplicatesIgnoringCaseAndOrigin(): void
    {
        $index = new UsageIndex();
        $index->build([
            new Usage('Demo\Greeter', 'greet', 'method-call', 'Demo\App', 'run', 'src/App.php', 5, false),
            new Usage('demo\greeter', 'GREET', 'method-call', 'Demo\Other', 'other', 'src/App.php', 5, true),
        ]);

        self::assertCount(1, $index->forType('Demo\Greeter'));
        self::assertCount(1, $index->forMember('Demo\Greeter', 'greet'));
    }

    public function testBuildKeepsUsagesDifferingInKindOrLocation(): void
    {
        $index = new UsageIndex();
        $index->build([
            new Usage('Demo\Greeter', null, 'new', null, null, 'src/App.php', 5, false),
            new Usage('Demo\Greeter', null, 'instanceof', null, null, 'src/App.php', 5, false),
            new Usage('Demo\Greeter', null, 'new', null, null, 'src/App.php', 9, false),
        ]);

        self::assertCount(3, $index->forType('Demo\Greeter'));
    }

    public function testBuildIndexesOutgoingCallsOfEveryOrigin(): void
    {
        $index = new UsageIndex();
        $index->build([
            new Usage('Demo\Greeter', 'greet', 'method-call', 'Demo\App', 'run', 'src/App.php', 5, false),
            new Usage('Demo\Clock', null, 'new', 'Demo\App', 'boot', 'src/App.php', 12, false),
        ]);

        self::assertCount(1, $index->callsFrom('Demo\App', 'run'));
        self::assertCount(1, $index->callsFrom('Demo\App', 'boot'));
        self::assertCount(2, $index->callsFromType('Demo\App'));
    }

    public function testForTypeReturnsUsagesSortedByFileThenLine(): void
    {
        $index = new UsageIndex();
        $index->build([
            new Usage('Demo\Greeter', null, 'new', null, null, 'src/Z.php', 2, false),
            new Usage('Demo\Greeter', null, 'new', null, null, 'src/A.php', 9, false),
            new Usage('Demo\Greeter', null, 'new', null, null, 'src/A.php', 3, false),
        ]);

        $usages = $index->forType('demo\greeter');

        self::assertCount(3, $usages);
        self::assertSame('src/A.php', $usages[0]->file);
        self::assertSame(3, $usages[0]->line);
        self::assertSame('src/A.php', $usages[1]->file);
        self::assertSame(9, $usages[1]->line);
        self::assertSame('src/Z.php', $usages[2]->file);
        self::assertSame(2, $usages[2]->line);
    }

    public function testForTypeReturnsEmptyListForUnknownType(): void
    {
        self::assertSame([], (new UsageIndex())->forType('Demo\Missing'));
    }

    public function testForTypeDropsDevUsagesWhenDevIsExcluded(): void
    {
        $index = new UsageIndex();
        $index->build([
            new Usage('Demo\Greeter', null, 'new', 'Demo\App', 'run', 'src/App.php', 5, false),
            new Usage('Demo\Greeter', null, 'new', 'Tests\GreeterTest', 'testGreet', 'tests/GreeterTest.php', 9, true),
        ]);

        $production = $index->forType('Demo\Greeter', false);

        self::assertCount(2, $index->forType('Demo\Greeter'));
        self::assertCount(1, $production);
        self::assertSame('src/App.php', $production[0]->file);
    }

    public function testForMemberReturnsOnlyUsagesOfThatMember(): void
    {
        $index = new UsageIndex();
        $index->build([
            new Usage('Demo\Greeter', 'greet', 'method-call', null, null, 'src/App.php', 5, false),
            new Usage('Demo\Greeter', 'farewell', 'method-call', null, null, 'src/App.php', 6, false),
            new Usage('Demo\Greeter', null, 'new', null, null, 'src/App.php', 4, false),
        ]);

        $usages = $index->forMember('DEMO\GREETER', 'GREET');

        self::assertCount(1, $usages);
        self::assertSame('greet', $usages[0]->member);
        self::assertSame(5, $usages[0]->line);
    }

    public function testForMemberReturnsEmptyListForUnknownMember(): void
    {
        self::assertSame([], (new UsageIndex())->forMember('Demo\Greeter', 'missing'));
    }

    public function testForMemberDropsDevUsagesWhenDevIsExcluded(): void
    {
        $index = new UsageIndex();
        $index->build([
            new Usage('Demo\Greeter', 'greet', 'method-call', 'Demo\App', 'run', 'src/App.php', 5, false),
            new Usage('Demo\Greeter', 'greet', 'method-call', 'Tests\GreeterTest', 'testGreet', 'tests/GreeterTest.php', 9, true),
        ]);

        $production = $index->forMember('Demo\Greeter', 'greet', false);

        self::assertCount(1, $production);
        self::assertSame('src/App.php', $production[0]->file);
    }

    public function testForTypeGroupedOrdersGroupsFromInheritanceToIncidentalKinds(): void
    {
        $index = new UsageIndex();
        $index->build([
            new Usage('Demo\Greeter', null, 'type', null, null, 'src/App.php', 30, false),
            new Usage('Demo\Greeter', 'greet', 'method-call', null, null, 'src/App.php', 20, false),
            new Usage('Demo\Greeter', null, 'new', null, null, 'src/App.php', 15, false),
            new Usage('Demo\Greeter', null, 'extends', null, null, 'src/Child.php', 4, false),
        ]);

        $groups = $index->forTypeGrouped('Demo\Greeter', true);

        self::assertSame(['extends', 'new', 'method-call', 'type'], array_keys($groups));
        self::assertCount(1, $groups['new']);
        self::assertSame(15, $groups['new'][0]->line);
    }

    public function testForTypeGroupedExcludesDevUsagesWhenAsked(): void
    {
        $index = new UsageIndex();
        $index->build([
            new Usage('Demo\Greeter', null, 'new', 'Demo\App', 'run', 'src/App.php', 5, false),
            new Usage('Demo\Greeter', null, 'instanceof', 'Tests\GreeterTest', 'testGreet', 'tests/GreeterTest.php', 9, true),
        ]);

        self::assertSame(['new'], array_keys($index->forTypeGrouped('Demo\Greeter', false)));
        self::assertSame(['new', 'instanceof'], array_keys($index->forTypeGrouped('Demo\Greeter', true)));
    }

    public function testForMemberGroupedGroupsOnlyTheRequestedMember(): void
    {
        $index = new UsageIndex();
        $index->build([
            new Usage('Demo\Greeter', 'greet', 'method-call', null, null, 'src/App.php', 5, false),
            new Usage('Demo\Greeter', 'greet', 'static-call', null, null, 'src/App.php', 7, false),
            new Usage('Demo\Greeter', 'farewell', 'method-call', null, null, 'src/App.php', 9, false),
        ]);

        $groups = $index->forMemberGrouped('Demo\Greeter', 'greet', true);

        self::assertSame(['static-call', 'method-call'], array_keys($groups));
        self::assertCount(1, $groups['method-call']);
        self::assertSame(5, $groups['method-call'][0]->line);
    }

    public function testForMemberGroupedReturnsEmptyArrayForUnknownMember(): void
    {
        self::assertSame([], (new UsageIndex())->forMemberGrouped('Demo\Greeter', 'missing', true));
    }

    public function testGroupByKindSortsUnknownKindsAfterKnownOnesByName(): void
    {
        $groups = (new UsageIndex())->groupByKind([
            new Usage('Demo\Greeter', null, 'zeta-kind', null, null, 'src/App.php', 1, false),
            new Usage('Demo\Greeter', null, 'alpha-kind', null, null, 'src/App.php', 2, false),
            new Usage('Demo\Greeter', null, 'implements', null, null, 'src/App.php', 3, false),
        ]);

        self::assertSame(['implements', 'alpha-kind', 'zeta-kind'], array_keys($groups));
    }

    public function testFilteredReturnsEveryUsageWhenDevIsIncluded(): void
    {
        $production = new Usage('Demo\Greeter', null, 'new', null, null, 'src/App.php', 5, false);
        $dev = new Usage('Demo\Greeter', null, 'new', null, null, 'tests/AppTest.php', 9, true);

        self::assertSame([$production, $dev], (new UsageIndex())->filtered([$production, $dev], true));
        self::assertSame([$production], (new UsageIndex())->filtered([$production, $dev], false));
    }

    public function testIndexOriginIgnoresNonCallKindsAndUnknownOrigins(): void
    {
        $index = new UsageIndex();
        $index->indexOrigin(new Usage('Demo\Base', null, 'extends', 'Demo\App', 'run', 'src/App.php', 3, false));
        $index->indexOrigin(new Usage('Demo\Clock', null, 'new', null, null, 'src/App.php', 4, false));
        $index->indexOrigin(new Usage('Demo\Clock', null, 'new', 'Demo\App', null, 'src/App.php', 5, false));

        self::assertSame([], $index->callsFrom('Demo\App', 'run'));
        self::assertCount(1, $index->callsFromType('Demo\App'));
    }

    public function testCallsFromReportsOnlyCallKindsOfThatMemberOrderedByLine(): void
    {
        $index = new UsageIndex();
        $index->build([
            new Usage('Demo\Clock', 'now', 'method-call', 'Demo\App', 'run', 'src/App.php', 20, false),
            new Usage('Demo\Greeter', null, 'new', 'Demo\App', 'run', 'src/App.php', 12, false),
            new Usage('Demo\Marker', null, 'instanceof', 'Demo\App', 'run', 'src/App.php', 8, false),
            new Usage('Demo\Other', 'ping', 'method-call', 'Demo\App', 'boot', 'src/App.php', 30, false),
        ]);

        $calls = $index->callsFrom('DEMO\APP', 'RUN');

        self::assertCount(2, $calls);
        self::assertSame('Demo\Greeter', $calls[0]->targetFqcn);
        self::assertSame('Demo\Clock', $calls[1]->targetFqcn);
    }

    public function testCallsFromDeduplicatesRepeatedTargetsKeepingTheFirstLine(): void
    {
        $index = new UsageIndex();
        $index->build([
            new Usage('Demo\Clock', 'now', 'method-call', 'Demo\App', 'run', 'src/App.php', 25, false),
            new Usage('Demo\Clock', 'now', 'method-call', 'Demo\App', 'run', 'src/App.php', 18, false),
            new Usage('Demo\Clock', 'tick', 'method-call', 'Demo\App', 'run', 'src/App.php', 19, false),
        ]);

        $calls = $index->callsFrom('Demo\App', 'run');

        self::assertCount(2, $calls);
        self::assertSame(18, $calls[0]->line);
        self::assertSame('now', $calls[0]->member);
        self::assertSame('tick', $calls[1]->member);
    }

    public function testCallsFromReturnsEmptyListForUnknownMember(): void
    {
        self::assertSame([], (new UsageIndex())->callsFrom('Demo\App', 'missing'));
    }

    public function testCallsFromTypeCollectsTheCallsOfEveryMember(): void
    {
        $index = new UsageIndex();
        $index->build([
            new Usage('Demo\Clock', 'now', 'method-call', 'Demo\App', 'run', 'src/App.php', 20, false),
            new Usage('Demo\Greeter', 'greet', 'static-call', 'Demo\App', 'boot', 'src/App.php', 10, false),
            new Usage('Demo\Config', 'KEY', 'class-const', 'Demo\App', null, 'src/App.php', 5, false),
        ]);

        $calls = $index->callsFromType('Demo\App');

        self::assertCount(3, $calls);
        self::assertSame('Demo\Config', $calls[0]->targetFqcn);
        self::assertSame('Demo\Greeter', $calls[1]->targetFqcn);
        self::assertSame('Demo\Clock', $calls[2]->targetFqcn);
    }

    public function testCallsFromTypeReturnsEmptyListForUnknownType(): void
    {
        self::assertSame([], (new UsageIndex())->callsFromType('Demo\Missing'));
    }

    public function testDeduplicateTargetsKeepsOneEntryPerTargetMemberAndKind(): void
    {
        $first = new Usage('Demo\Clock', 'now', 'method-call', null, null, 'src/App.php', 4, false);
        $later = new Usage('demo\clock', 'NOW', 'method-call', null, null, 'src/App.php', 9, false);
        $other = new Usage('Demo\Clock', 'now', 'static-call', null, null, 'src/App.php', 6, false);

        $unique = (new UsageIndex())->deduplicateTargets([$later, $other, $first]);

        self::assertSame([$first, $other], $unique);
    }

    public function testSortedOrdersByFilePathThenLine(): void
    {
        $first = new Usage('Demo\Greeter', null, 'new', null, null, 'src/A.php', 7, false);
        $second = new Usage('Demo\Greeter', null, 'new', null, null, 'src/B.php', 1, false);
        $third = new Usage('Demo\Greeter', null, 'new', null, null, 'src/B.php', 8, false);

        self::assertSame([$first, $second, $third], (new UsageIndex())->sorted([$third, $first, $second]));
    }
}

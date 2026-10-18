<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Source\GlobMatcher;
use DecisionsLint\Source\Target;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Rule\TargetSelector
 * @uses \DecisionsLint\Source\GlobMatcher
 * @uses \DecisionsLint\Source\Target
 */
#[CoversClass(TargetSelector::class)]
#[UsesClass(GlobMatcher::class)]
#[UsesClass(Target::class)]
final class TargetSelectorTest extends TestCase
{
    /**
     * @dataProvider providerSelects
     */
    #[DataProvider('providerSelects')]
    public function testSelectsFiltersByKindVisibilityStaticAndName(TargetSelector $selector, bool $expected): void
    {
        $target = new Target('method', '__construct', 'A::__construct', '', 'A', 'src/A.php', 1, 1, 'public static function __construct()', 'public', ['public', 'static'], 'D', 'C', 'F');
        self::assertSame($expected, $selector->selects($target));
    }

    /**
     * @return iterable<string, array{TargetSelector, bool}>
     */
    public static function providerSelects(): iterable
    {
        yield 'kind only' => [new TargetSelector('method'), true];
        yield 'other kind' => [new TargetSelector('property'), false];
        yield 'matching visibility' => [new TargetSelector('method', ['public', 'protected']), true];
        yield 'other visibility' => [new TargetSelector('method', ['private']), false];
        yield 'static wanted' => [new TargetSelector('method', [], [], [], true), true];
        yield 'non-static wanted' => [new TargetSelector('method', [], [], [], false), false];
        yield 'name glob' => [new TargetSelector('method', [], ['__*']), true];
        yield 'name glob misses' => [new TargetSelector('method', [], ['analyze*']), false];
        yield 'excluded name' => [new TargetSelector('method', [], [], ['__*']), false];
    }

    public function testExposesItsFields(): void
    {
        $selector = new TargetSelector('property', ['private'], ['*Id'], ['legacy*'], false);
        self::assertSame('property', $selector->kind);
        self::assertSame(['private'], $selector->visibility);
        self::assertSame(['*Id'], $selector->names);
        self::assertSame(['legacy*'], $selector->excludeNames);
        self::assertFalse($selector->static);
    }
}

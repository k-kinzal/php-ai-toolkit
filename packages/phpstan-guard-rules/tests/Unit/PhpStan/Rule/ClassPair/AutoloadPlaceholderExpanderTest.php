<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ClassPair;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ClassPair\AutoloadPlaceholderExpander;
use Toolkit\PhpStan\Rule\ClassPair\ComposerAutoloadMap;

/**
 * @covers \Toolkit\PhpStan\Rule\ClassPair\AutoloadPlaceholderExpander
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ComposerAutoloadMap
 */
#[CoversClass(AutoloadPlaceholderExpander::class)]
#[UsesClass(ComposerAutoloadMap::class)]
final class AutoloadPlaceholderExpanderTest extends TestCase
{
    public function testExpandReplacesEachPlaceholderWithItsNamespace(): void
    {
        $expander = new AutoloadPlaceholderExpander(new ComposerAutoloadMap([['App', 'src'], ['Tests', 'tests']]));

        self::assertSame('App\{name}', $expander->expand('${src}\{name}'));
        self::assertSame('Tests\Unit\{name}Test', $expander->expand('${tests/Unit}\{name}Test'));
        self::assertSame('App\Fake\Fake{name}', $expander->expand('${src}\Fake\Fake{name}'));
    }

    public function testExpandKeepsPatternsWithoutPlaceholders(): void
    {
        $expander = new AutoloadPlaceholderExpander(new ComposerAutoloadMap([]));

        self::assertSame('Plain\{name}', $expander->expand('Plain\{name}'));
    }

    public function testExpandReturnsNullForUnmappedDirectory(): void
    {
        $expander = new AutoloadPlaceholderExpander(new ComposerAutoloadMap([['App', 'src']]));

        self::assertNull($expander->expand('${lib}\{name}'));
        self::assertNull($expander->expand('${src}\{name}${tests}'));
    }

    public function testExpandDropsLeadingSeparatorOfFallbackNamespace(): void
    {
        $expander = new AutoloadPlaceholderExpander(new ComposerAutoloadMap([['', 'src']]));

        self::assertSame('{name}', $expander->expand('${src}\{name}'));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Rector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Tests\Rector\RectorFixtureRunner;
use Toolkit\Rector\RiskyRules;
use Toolkit\Rector\SafeRules;

/**
 * @covers \Toolkit\Rector\SafeRules
 * @uses \Toolkit\Rector\RiskyRules
 * @large
 */
#[CoversClass(SafeRules::class)]
#[UsesClass(RiskyRules::class)]
#[Large]
final class SafeRulesTest extends TestCase
{
    public function testRulesApplySafeChangesWithoutRethrowingAnEmptyCatch(): void
    {
        $result = (new RectorFixtureRunner())->run('', 'SafeSet', 'safe-rules');
        self::assertSame(0, $result['exitCode'], $result['output']);
        self::assertSame($result['expected'], $result['actual']);
        self::assertTrue($result['idempotent'], $result['output']);
    }

    public function testRulesKeepSafeAndRiskyGroupsDisjoint(): void
    {
        self::assertSame([], array_intersect(SafeRules::rules(), RiskyRules::rules()));
    }
}

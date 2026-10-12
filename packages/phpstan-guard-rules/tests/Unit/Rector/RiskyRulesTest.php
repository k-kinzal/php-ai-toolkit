<?php

declare(strict_types=1);

namespace Tests\Unit\Rector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;
use Tests\Rector\RectorFixtureRunner;
use Toolkit\Rector\RiskyRules;

/**
 * @covers \Toolkit\Rector\RiskyRules
 * @large
 */
#[CoversClass(RiskyRules::class)]
#[Large]
final class RiskyRulesTest extends TestCase
{
    public function testRulesApplyRiskyChangesWithoutFormattingPublicPhpDoc(): void
    {
        $result = (new RectorFixtureRunner())->run('', 'RiskySet', 'risky-rules');
        self::assertSame(0, $result['exitCode'], $result['output']);
        self::assertSame($result['expected'], $result['actual']);
        self::assertTrue($result['idempotent'], $result['output']);
    }

    public function testRulesComposeWithSafeRulesInOneConfiguration(): void
    {
        $result = (new RectorFixtureRunner())->run('', 'CombinedRules', 'all-rules');
        self::assertSame(0, $result['exitCode'], $result['output']);
        self::assertSame($result['expected'], $result['actual']);
        self::assertTrue($result['idempotent'], $result['output']);
    }
}

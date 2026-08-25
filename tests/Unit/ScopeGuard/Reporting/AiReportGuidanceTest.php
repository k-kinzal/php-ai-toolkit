<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Reporting\AiReportGuidance;

/**
 * @covers \Toolkit\ScopeGuard\Reporting\AiReportGuidance
 */
#[CoversClass(AiReportGuidance::class)]
final class AiReportGuidanceTest extends TestCase
{
    public function testGuidanceOpensWithTheGuidanceKey(): void
    {
        self::assertStringStartsWith("guidance:\n", (new AiReportGuidance())->guidance());
    }

    public function testGuidanceEndsWithTheViolationsKey(): void
    {
        self::assertStringEndsWith("violations:\n", (new AiReportGuidance())->guidance());
    }

    public function testGuidanceWarnsAgainstDeletingTheTag(): void
    {
        self::assertStringContainsString('do not delete an @visibility tag', (new AiReportGuidance())->guidance());
    }
}

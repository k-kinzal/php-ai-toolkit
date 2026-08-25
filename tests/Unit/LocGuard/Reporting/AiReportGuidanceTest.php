<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Reporting\AiReportGuidance;

/**
 * @covers \Toolkit\LocGuard\Reporting\AiReportGuidance
 */
#[CoversClass(AiReportGuidance::class)]
final class AiReportGuidanceTest extends TestCase
{
    public function testGuidanceReturnsAiRemediationText(): void
    {
        $guidance = (new AiReportGuidance())->guidance();

        self::assertStringContainsString('guidance:', $guidance);
        self::assertStringContainsString('violations:', $guidance);
    }
}

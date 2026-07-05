<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\Reporting\AiReportGuidance;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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

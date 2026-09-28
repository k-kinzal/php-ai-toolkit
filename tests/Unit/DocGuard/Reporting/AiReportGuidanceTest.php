<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Reporting\AiReportGuidance;

/**
 * @covers \Toolkit\DocGuard\Reporting\AiReportGuidance
 */
#[CoversClass(AiReportGuidance::class)]
final class AiReportGuidanceTest extends TestCase
{
    public function testGuidanceForbidsStructuralChangesByAgents(): void
    {
        $guidance = (new AiReportGuidance())->guidance();

        self::assertStringStartsWith("guidance:\n", $guidance);
        self::assertStringContainsString('Do not add sections or documents. Write new information inside the existing section', $guidance);
        self::assertStringContainsString('ask a human to update the DocGuard config', $guidance);
        self::assertStringContainsString('do not regenerate it with doc-guard --generate', $guidance);
        self::assertStringEndsWith("violations:\n", $guidance);
    }
}

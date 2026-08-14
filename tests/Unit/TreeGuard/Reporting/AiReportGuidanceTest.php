<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Reporting;

use PhpAiToolkit\TreeGuard\Reporting\AiReportGuidance;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AiReportGuidance::class)]
final class AiReportGuidanceTest extends TestCase
{
    public function testGuidanceListsRemediationAdvice(): void
    {
        $guidance = (new AiReportGuidance())->guidance();

        self::assertStringContainsString('guidance:', $guidance);
        self::assertStringContainsString('do not relax tree.yaml limits', $guidance);
        self::assertStringContainsString('max_files or max_dirs', $guidance);
        self::assertStringContainsString('missing required files', $guidance);
        self::assertStringContainsString("violations:\n", $guidance);
    }
}

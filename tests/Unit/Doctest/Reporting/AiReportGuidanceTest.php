<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Reporting;

use PhpAiToolkit\Doctest\Reporting\AiReportGuidance;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AiReportGuidance::class)]
final class AiReportGuidanceTest extends TestCase
{
    public function testGuidanceTellsAnAgentWhatToDecideAndWhatNotToDelete(): void
    {
        $guidance = (new AiReportGuidance())->guidance();

        self::assertStringStartsWith("guidance:\n", $guidance);
        self::assertStringContainsString('decide which one is wrong before editing either', $guidance);
        self::assertStringContainsString('Deleting an example', $guidance);
        self::assertStringContainsString('vendor/bin/doctest --filter=<id>', $guidance);
        self::assertStringEndsWith("failures:\n", $guidance);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Reporting\Reporter
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Reporting\Reporter::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class ReporterTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testHasErrorsRecommendationsDoNotFailChecks(): void
    {
        $reporter = new \Guard\Reporting\Reporter();
        $findings = [new \Guard\Reporting\Finding('a.json', 'style', 'recommended', 'Choose A.')];
        self::assertFalse($reporter->hasErrors($findings));
        self::assertStringContainsString('warning:', $reporter->render($findings, [], 'text', 'checked'));
        self::assertStringContainsString('"success": true', $reporter->render($findings, [], 'json', 'checked'));
    }

    /**
     * @throws JsonException
     */
    public function testRenderIncludesStableRuleIdentifiersInJson(): void
    {
        $finding = new \Guard\Reporting\Finding('x.json', 'worker.minimum', 'required', 'Set workers to 1.');
        $report = (new \Guard\Reporting\Reporter())->render([$finding], [], 'json', 'checked');
        self::assertStringContainsString('"rule": "worker.minimum"', $report);
        self::assertStringContainsString('"success": false', $report);
    }
}

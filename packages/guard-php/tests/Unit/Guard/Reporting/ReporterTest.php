<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Reporting;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Reporting\Reporter
 * @uses \Toolkit\Guard\Execution\FileChange
 * @uses \Toolkit\Guard\Reporting\Finding
 */
#[CoversClass(\Toolkit\Guard\Reporting\Reporter::class)]
#[UsesClass(\Toolkit\Guard\Execution\FileChange::class)]
#[UsesClass(\Toolkit\Guard\Reporting\Finding::class)]
final class ReporterTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testHasErrorsRecommendationsDoNotFailChecks(): void
    {
        $reporter = new \Toolkit\Guard\Reporting\Reporter();
        $findings = [new \Toolkit\Guard\Reporting\Finding('a.json', 'style', 'recommended', 'Choose A.')];
        self::assertFalse($reporter->hasErrors($findings));
        self::assertStringContainsString('warning:', $reporter->render($findings, [], 'text', 'checked'));
        self::assertStringContainsString('"success": true', $reporter->render($findings, [], 'json', 'checked'));
    }

    /**
     * @throws JsonException
     */
    public function testRenderIncludesStableRuleIdentifiersInJson(): void
    {
        $finding = new \Toolkit\Guard\Reporting\Finding('x.json', 'worker.minimum', 'required', 'Set workers to 1.');
        $report = (new \Toolkit\Guard\Reporting\Reporter())->render([$finding], [], 'json', 'checked');
        self::assertStringContainsString('"rule": "worker.minimum"', $report);
        self::assertStringContainsString('"success": false', $report);
    }
}

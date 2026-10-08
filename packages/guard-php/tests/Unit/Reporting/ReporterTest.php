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

    public function testCountsSeparatesSeverityFromRepairCapability(): void
    {
        self::assertSame(['errors' => 1, 'warnings' => 1, 'fixable' => 1], (new \Guard\Reporting\Reporter())->counts([
            new \Guard\Reporting\Finding('a', 'x', 'required', 'Fix it.', true),
            new \Guard\Reporting\Finding('b', 'y', 'recommended', 'Review it.'),
        ]));
    }

    public function testAiKeepsDiffsAndMessagesWithoutAnsi(): void
    {
        $report = (new \Guard\Reporting\Reporter())->ai([new \Guard\Reporting\Finding('a', 'x', 'required', 'Value is wrong. Set it to 2.', true)], [new \Guard\Execution\FileChange('a', 'old', 'new')], 'would change');
        self::assertStringContainsString('fixable=yes', $report);
        self::assertStringContainsString('Value is wrong. Set it to 2.', $report);
        self::assertStringContainsString('-old', $report);
        self::assertStringContainsString('+new', $report);
        self::assertStringNotContainsString("\033", $report);
    }

    public function testHumanGroupsFindingsAndEscapesDocumentMarkup(): void
    {
        $report = (new \Guard\Reporting\Reporter())->human([new \Guard\Reporting\Finding('a.xml', 'x', 'required', '<info>literal</info>', true)], [], 'checked', false);
        self::assertStringContainsString('<info>literal</info>', $report);
        self::assertStringContainsString('1 error · 0 warnings · 1 fixable', $report);
        self::assertStringContainsString('guard fix --dry-run', $report);
    }

    public function testActionNoteExplainsWhyAPlanWasNotWritten(): void
    {
        self::assertStringContainsString('No files were written', (new \Guard\Reporting\Reporter())->actionNote('blocked'));
    }
    public function testSummaryCountsAffectedFilesAndUsesSingularLabels(): void
    {
        $findings = [new \Guard\Reporting\Finding('a', 'x', 'recommended', 'Review.')];
        self::assertSame('0 errors, 1 warning, 0 fixable, 1 file with findings', (new \Guard\Reporting\Reporter())->summary($findings, [], 'checked', ', '));
    }
}

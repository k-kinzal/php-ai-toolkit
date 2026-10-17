<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use Guard\Diagnostic\Finding;
use Guard\Policy\FileChange;
use Guard\Reporting\ChangeDiff;
use Guard\Reporting\Reporter;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Reporting\Reporter
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Reporting\ChangeDiff
 */
#[CoversClass(Reporter::class)]
#[UsesClass(ChangeDiff::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Finding::class)]
final class ReporterTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testHasErrorsRecommendationsDoNotFailChecks(): void
    {
        $reporter = new Reporter();
        $findings = [new Finding('a.json', 'style', 'recommended', 'Choose A.')];
        self::assertFalse($reporter->hasErrors($findings));
        self::assertStringContainsString('warning:', $reporter->render($findings, [], 'text', 'checked'));
        self::assertStringContainsString('"success": true', $reporter->render($findings, [], 'json', 'checked'));
    }

    /**
     * @throws JsonException
     */
    public function testRenderIncludesStableRuleIdentifiersInJson(): void
    {
        $finding = new Finding('x.json', 'worker.minimum', 'required', 'Set workers to 1.');
        $report = (new Reporter())->render([$finding], [], 'json', 'checked');
        self::assertStringContainsString('"rule": "worker.minimum"', $report);
        self::assertStringContainsString('"success": false', $report);
    }

    public function testCountsSeparatesSeverityFromRepairCapability(): void
    {
        self::assertSame(['errors' => 1, 'warnings' => 1, 'fixable' => 1], (new Reporter())->counts([
            new Finding('a', 'x', 'required', 'Fix it.', true),
            new Finding('b', 'y', 'recommended', 'Review it.'),
        ]));
    }

    public function testAiKeepsDiffsAndMessagesWithoutAnsi(): void
    {
        $report = (new Reporter())->ai([new Finding('a', 'x', 'required', 'Value is wrong. Set it to 2.', true)], [new FileChange('a', 'old', 'new')], 'would change');
        self::assertStringContainsString('fixable=yes', $report);
        self::assertStringContainsString('Value is wrong. Set it to 2.', $report);
        self::assertStringContainsString('-old', $report);
        self::assertStringContainsString('+new', $report);
        self::assertStringNotContainsString("\033", $report);
    }

    public function testHumanGroupsFindingsAndEscapesDocumentMarkup(): void
    {
        $report = (new Reporter())->human([new Finding('a.xml', 'x', 'required', '<info>literal</info>', true)], [], 'checked', false);
        self::assertStringContainsString('<info>literal</info>', $report);
        self::assertStringContainsString('1 error · 0 warnings · 1 fixable', $report);
        self::assertStringContainsString('guard fix --dry-run', $report);
    }

    public function testActionNoteExplainsWhyAPlanWasNotWritten(): void
    {
        self::assertStringContainsString('No files were written', (new Reporter())->actionNote('blocked'));
    }
    public function testSummaryCountsAffectedFilesAndUsesSingularLabels(): void
    {
        $findings = [new Finding('a', 'x', 'recommended', 'Review.')];
        self::assertSame('0 errors, 1 warning, 0 fixable, 1 file with findings', (new Reporter())->summary($findings, [], 'checked', ', '));
    }
}

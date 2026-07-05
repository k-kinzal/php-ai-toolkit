<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use function array_key_exists;
use function count;
use function ltrim;
use function max;
use function sprintf;
use function str_repeat;
use function strlen;
use function trim;

/**
 * Formats PHPUnit issues with terminal-oriented context and color tags.
 */
final class TestIssueHumanFormatter
{
    /**
     * Creates the human renderer from reusable formatting collaborators.
     */
    public function __construct(
        /** @readonly */
        private TestIssuePathFormatter $pathFormatter,
        /** @readonly */
        private TestIssueSourceReader $sourceReader,
        /** @readonly */
        private TestIssueTypePresentation $typePresentation,
        /** @readonly */
        private TestIssueSummary $summary,
        /** @readonly */
        private TestIssueBlockIndenter $blockIndenter,
        /** @readonly */
        private TestIssueGutter $gutter,
    ) {
    }

    /**
     * Formats non-empty test issues for human consumption.
     *
     * @param list<TestIssue> $issues non-empty list of issues
     */
    public function format(array $issues): string
    {
        $grouped = [];
        foreach ($issues as $issue) {
            if (!array_key_exists($issue->testFile, $grouped)) {
                $grouped[$issue->testFile] = [];
            }
            $grouped[$issue->testFile][] = $issue;
        }

        $output = '';
        foreach ($grouped as $file => $fileIssues) {
            $output .= $this->fileBlock($file, $fileIssues);
        }

        return $output . "\n" . $this->summaryLine($issues, count($grouped));
    }

    /**
     * Formats all issues for one test file.
     *
     * @param list<TestIssue> $fileIssues issues belonging to the same file
     */
    public function fileBlock(string $file, array $fileIssues): string
    {
        $output = sprintf("\n <fg=cyan>%s</>\n", $this->pathFormatter->relative($file));
        $gutterWidth = $this->gutter->width($fileIssues);

        foreach ($fileIssues as $issue) {
            $output .= "\n";
            $output .= $this->issueBlock($issue, $gutterWidth);
        }

        return $output;
    }

    /**
     * Builds the final human-readable summary line.
     *
     * @param list<TestIssue> $issues formatted issues
     */
    public function summaryLine(array $issues, int $fileCount): string
    {
        $counts = $this->summary->countByType($issues);
        $message = sprintf(
            'Found %s in %d %s',
            $this->summary->buildCountSummary($counts, count($issues)),
            $fileCount,
            $fileCount === 1 ? 'test file' : 'test files',
        );

        $hasFailuresOrErrors = ($counts[TestIssue::TYPE_FAILED] ?? 0) > 0
            || ($counts[TestIssue::TYPE_ERROR] ?? 0) > 0;

        if ($hasFailuresOrErrors) {
            return sprintf(" <error> %s </error>\n", $message);
        }

        return sprintf(" <comment> %s </comment>\n", $message);
    }

    /**
     * Formats one test issue with code context.
     */
    public function issueBlock(TestIssue $issue, int $gutterWidth): string
    {
        $output = '';
        $lineStr = $issue->testLine > 0 ? (string) $issue->testLine : '?';
        $emptyGutter = str_repeat(' ', $gutterWidth);
        $codeLine = $issue->testLine > 0 ? $this->sourceReader->read($issue->testFile, $issue->testLine) : null;

        if ($codeLine !== null) {
            $trimmedCode = ltrim($codeLine);
            $leadingSpaces = strlen($codeLine) - strlen($trimmedCode);
            $caretLength = max(1, strlen(trim($trimmedCode)));
            $output .= sprintf("  <fg=blue>%s</> | %s\n", $this->gutter->line($lineStr, $gutterWidth), $codeLine);
            $output .= sprintf("  %s | <fg=red>%s</>\n", $emptyGutter, str_repeat(' ', $leadingSpaces) . str_repeat('^', $caretLength));
        }

        $output .= sprintf("  <fg=%s>%s</>: %s\n", $this->typePresentation->color($issue->type), $this->typePresentation->label($issue->type), $issue->testName);
        $output .= sprintf("  %s\n", $issue->message);

        if ($issue->diff !== null && trim($issue->diff) !== '') {
            $output .= $this->blockIndenter->indent($issue->diff);
        }

        if ($issue->sourceFile !== null && $issue->sourceLine !== null) {
            $output .= sprintf("  <fg=yellow>Source:</> %s:%d\n", $this->pathFormatter->relative($issue->sourceFile), $issue->sourceLine);
        }

        return $output;
    }
}

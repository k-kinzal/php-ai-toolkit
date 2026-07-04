<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use function array_key_exists;
use function count;
use function explode;
use function file;
use function implode;
use function is_array;
use function is_file;
use function ltrim;
use function max;

use PhpAiToolkit\Shared\AgentDetector;
use PhpAiToolkit\Shared\FormatMode;

use function rtrim;
use function sprintf;
use function str_pad;

use const STR_PAD_LEFT;

use function str_repeat;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;

/**
 * Dual-mode formatter for PHPUnit test issues.
 *
 * Produces structured plain text for AI agents or rich colored output
 * for human developers, following the same design principles as
 * AiRulesErrorFormatter for PHPStan.
 */
final class TestIssueFormatter
{
    private readonly string $mode;

    /**
     * @param AgentDetector $agentDetector resolves the current output format mode
     * @param string $basePath base directory for computing relative paths
     */
    public function __construct(
        AgentDetector $agentDetector,
        private readonly string $basePath,
    ) {
        $this->mode = $agentDetector->resolveMode();
    }

    /**
     * Formats a list of test issues for either human or AI consumption.
     *
     * Returns an empty string when there are no issues to report.
     *
     * @param list<TestIssue> $issues collected test issues
     */
    public function format(array $issues): string
    {
        if (count($issues) === 0) {
            return '';
        }

        if ($this->mode === FormatMode::AI) {
            return $this->formatForAi($issues);
        }

        return $this->formatForHuman($issues);
    }

    /**
     * Formats issues as structured plain text optimized for LLM context windows.
     *
     * Design principles applied:
     * - C2: Summary-first (primacy effect for LLM recall)
     * - C3: Self-contained blocks (no cross-referencing)
     * - C5: Deterministic ordering (file then line)
     * - path:line leading format (GCC/ESLint convention)
     * - Code context line included (reduces agent round-trips)
     * - Source location for bug navigation (PHPUnit-specific)
     *
     * @param list<TestIssue> $issues non-empty list of issues
     */
    private function formatForAi(array $issues): string
    {
        $counts = $this->countByType($issues);
        $output = sprintf("--- PHPUnit: %s ---\n", $this->buildCountSummary($counts, count($issues)));

        foreach ($issues as $issue) {
            $relativePath = $this->relativePath($issue->testFile);
            $typeLabel = $this->typeLabel($issue->type);

            $output .= sprintf(
                "\n%s:%d [%s]\n",
                $relativePath,
                $issue->testLine,
                $typeLabel,
            );
            $output .= sprintf("  %s\n", $issue->testName);
            $output .= sprintf("  %s\n", $issue->message);

            $codeLine = $this->readSourceLine($issue->testFile, $issue->testLine);
            if ($codeLine !== null) {
                $output .= sprintf("  > %s\n", trim($codeLine));
            }

            if ($issue->diff !== null && trim($issue->diff) !== '') {
                $output .= $this->indentBlock($issue->diff);
            }

            if ($issue->sourceFile !== null && $issue->sourceLine !== null) {
                $output .= sprintf(
                    "  Source: %s:%d\n",
                    $this->relativePath($issue->sourceFile),
                    $issue->sourceLine,
                );
            }
        }

        return $output;
    }

    /**
     * Formats issues with rich terminal output including code context and colors.
     *
     * Design principles applied:
     * - Header-first ordering (file path before code)
     * - Code context with caret pointers (28% higher repair scores)
     * - Color encoding (FAILED=red, ERROR=red, RISKY=yellow)
     * - Fixed-width line-number gutter
     *
     * @param list<TestIssue> $issues non-empty list of issues
     */
    private function formatForHuman(array $issues): string
    {
        $grouped = $this->groupIssuesByFile($issues);
        $output = '';

        foreach ($grouped as $file => $fileIssues) {
            $output .= $this->writeHumanFileIssues($file, $fileIssues);
        }

        $output .= "\n";
        $output .= $this->buildHumanSummaryLine($issues, count($grouped));

        return $output;
    }

    /**
     * @param list<TestIssue> $issues
     * @return array<string, list<TestIssue>>
     */
    private function groupIssuesByFile(array $issues): array
    {
        $grouped = [];
        foreach ($issues as $issue) {
            $file = $issue->testFile;
            if (!array_key_exists($file, $grouped)) {
                $grouped[$file] = [];
            }
            $grouped[$file][] = $issue;
        }

        return $grouped;
    }

    /**
     * @param list<TestIssue> $fileIssues
     */
    private function writeHumanFileIssues(string $file, array $fileIssues): string
    {
        $output = sprintf("\n <fg=cyan>%s</>\n", $this->relativePath($file));
        $gutterWidth = $this->calculateGutterWidth($fileIssues);

        foreach ($fileIssues as $issue) {
            $output .= "\n";
            $output .= $this->writeHumanIssue($issue, $gutterWidth);
        }

        return $output;
    }

    /**
     * @param list<TestIssue> $issues
     */
    private function buildHumanSummaryLine(array $issues, int $fileCount): string
    {
        $counts = $this->countByType($issues);
        $totalCount = count($issues);

        $message = sprintf(
            'Found %s in %d %s',
            $this->buildCountSummary($counts, $totalCount),
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
     * Formats a single issue for human-readable output with code context and carets.
     */
    private function writeHumanIssue(TestIssue $issue, int $gutterWidth): string
    {
        $output = '';

        $lineStr = $issue->testLine > 0 ? (string) $issue->testLine : '?';
        $gutter = str_pad($lineStr, $gutterWidth, ' ', STR_PAD_LEFT);
        $emptyGutter = str_repeat(' ', $gutterWidth);

        $codeLine = $issue->testLine > 0 ? $this->readSourceLine($issue->testFile, $issue->testLine) : null;

        if ($codeLine !== null) {
            $output .= sprintf("  <fg=blue>%s</> | %s\n", $gutter, $codeLine);

            $trimmedCode = ltrim($codeLine);
            $leadingSpaces = strlen($codeLine) - strlen($trimmedCode);
            $caretLength = max(1, strlen(rtrim($trimmedCode)));
            $carets = str_repeat(' ', $leadingSpaces) . str_repeat('^', $caretLength);

            $output .= sprintf("  %s | <fg=red>%s</>\n", $emptyGutter, $carets);
        }

        $typeColor = $this->typeColor($issue->type);
        $typeLabel = $this->typeLabel($issue->type);
        $output .= sprintf("  <fg=%s>%s</>: %s\n", $typeColor, $typeLabel, $issue->testName);
        $output .= sprintf("  %s\n", $issue->message);

        if ($issue->diff !== null && trim($issue->diff) !== '') {
            $output .= $this->indentBlock($issue->diff);
        }

        if ($issue->sourceFile !== null && $issue->sourceLine !== null) {
            $output .= sprintf(
                "  <fg=yellow>Source:</> %s:%d\n",
                $this->relativePath($issue->sourceFile),
                $issue->sourceLine,
            );
        }

        return $output;
    }

    /**
     * Returns the uppercase label for an issue type.
     *
     * @param TestIssue::TYPE_* $type the issue type constant
     */
    private function typeLabel(string $type): string
    {
        return match ($type) {
            TestIssue::TYPE_FAILED => 'FAILED',
            TestIssue::TYPE_ERROR => 'ERROR',
            TestIssue::TYPE_RISKY => 'RISKY',
            TestIssue::TYPE_SKIPPED => 'SKIPPED',
        };
    }

    /**
     * Returns the Symfony Console color name for an issue type.
     *
     * @param TestIssue::TYPE_* $type the issue type constant
     */
    private function typeColor(string $type): string
    {
        return match ($type) {
            TestIssue::TYPE_FAILED, TestIssue::TYPE_ERROR => 'red',
            TestIssue::TYPE_RISKY => 'yellow',
            TestIssue::TYPE_SKIPPED => 'cyan',
        };
    }

    /**
     * Counts issues by type.
     *
     * @param list<TestIssue> $issues the issues to count
     *
     * @return array<string, int> type to count mapping
     */
    private function countByType(array $issues): array
    {
        $counts = [];
        foreach ($issues as $issue) {
            if (!array_key_exists($issue->type, $counts)) {
                $counts[$issue->type] = 0;
            }
            $counts[$issue->type]++;
        }

        return $counts;
    }

    /**
     * Builds a human-readable count summary string.
     *
     * @param array<string, int> $counts type to count mapping
     * @param int $total total issue count
     */
    private function buildCountSummary(array $counts, int $total): string
    {
        $parts = [];

        $failedCount = $counts[TestIssue::TYPE_FAILED] ?? 0;
        if ($failedCount > 0) {
            $parts[] = sprintf('%d %s', $failedCount, $failedCount === 1 ? 'failure' : 'failures');
        }

        $errorCount = $counts[TestIssue::TYPE_ERROR] ?? 0;
        if ($errorCount > 0) {
            $parts[] = sprintf('%d %s', $errorCount, $errorCount === 1 ? 'error' : 'errors');
        }

        $riskyCount = $counts[TestIssue::TYPE_RISKY] ?? 0;
        if ($riskyCount > 0) {
            $parts[] = sprintf('%d risky', $riskyCount);
        }

        $skippedCount = $counts[TestIssue::TYPE_SKIPPED] ?? 0;
        if ($skippedCount > 0) {
            $parts[] = sprintf('%d skipped', $skippedCount);
        }

        if (count($parts) === 0) {
            return sprintf('%d %s', $total, $total === 1 ? 'issue' : 'issues');
        }

        return implode(', ', $parts);
    }

    /**
     * Indents a multi-line block (like a diff) with two spaces per line.
     */
    private function indentBlock(string $block): string
    {
        $output = '';
        $lines = explode("\n", trim($block));
        foreach ($lines as $line) {
            $output .= sprintf("  %s\n", $line);
        }

        return $output;
    }

    /**
     * Calculates the gutter width needed for line number alignment.
     *
     * @param list<TestIssue> $issues issues in the same file
     */
    private function calculateGutterWidth(array $issues): int
    {
        $maxLine = 1;
        foreach ($issues as $issue) {
            if ($issue->testLine > $maxLine) {
                $maxLine = $issue->testLine;
            }
        }

        return max(3, strlen((string) $maxLine));
    }

    /**
     * Computes a path relative to the base directory.
     */
    private function relativePath(string $absolutePath): string
    {
        $base = rtrim($this->basePath, '/') . '/';
        if (str_starts_with($absolutePath, $base)) {
            return substr($absolutePath, strlen($base));
        }

        return $absolutePath;
    }

    /**
     * Reads a single source line from a file.
     *
     * Returns null if the file does not exist or the line is out of range.
     */
    private function readSourceLine(string $filePath, int $line): ?string
    {
        if ($line <= 0 || !is_file($filePath)) {
            return null;
        }

        $lines = @file($filePath);
        if (!is_array($lines)) {
            return null;
        }

        $index = $line - 1;
        if (!array_key_exists($index, $lines)) {
            return null;
        }

        return rtrim($lines[$index], "\r\n");
    }
}

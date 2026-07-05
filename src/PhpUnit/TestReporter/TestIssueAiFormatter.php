<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use function count;
use function sprintf;
use function trim;

/**
 * Formats PHPUnit issues as compact plain text for AI agents.
 */
final class TestIssueAiFormatter
{
    /**
     * Creates the AI renderer from reusable formatting collaborators.
     */
    public function __construct(
        private readonly TestIssuePathFormatter $pathFormatter,
        private readonly TestIssueSourceReader $sourceReader,
        private readonly TestIssueTypePresentation $typePresentation,
        private readonly TestIssueSummary $summary,
        private readonly TestIssueBlockIndenter $blockIndenter,
    ) {
    }

    /**
     * Formats non-empty test issues for AI consumption.
     *
     * @param list<TestIssue> $issues non-empty list of issues
     */
    public function format(array $issues): string
    {
        $counts = $this->summary->countByType($issues);
        $output = sprintf("--- PHPUnit: %s ---\n", $this->summary->buildCountSummary($counts, count($issues)));

        foreach ($issues as $issue) {
            $output .= sprintf(
                "\n%s:%d [%s]\n",
                $this->pathFormatter->relative($issue->testFile),
                $issue->testLine,
                $this->typePresentation->label($issue->type),
            );
            $output .= sprintf("  %s\n", $issue->testName);
            $output .= sprintf("  %s\n", $issue->message);

            $codeLine = $this->sourceReader->read($issue->testFile, $issue->testLine);
            if ($codeLine !== null) {
                $output .= sprintf("  > %s\n", trim($codeLine));
            }

            if ($issue->diff !== null && trim($issue->diff) !== '') {
                $output .= $this->blockIndenter->indent($issue->diff);
            }

            if ($issue->sourceFile !== null && $issue->sourceLine !== null) {
                $output .= sprintf(
                    "  Source: %s:%d\n",
                    $this->pathFormatter->relative($issue->sourceFile),
                    $issue->sourceLine,
                );
            }
        }

        return $output;
    }
}

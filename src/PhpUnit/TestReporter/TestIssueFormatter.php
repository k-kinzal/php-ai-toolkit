<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

use function count;

use PhpAiToolkit\Shared\AgentDetector;
use PhpAiToolkit\Shared\FormatMode;

/**
 * Selects the PHPUnit issue renderer for the current execution context.
 */
final class TestIssueFormatter
{
    private readonly string $mode;

    private readonly TestIssueAiFormatter $aiFormatter;

    private readonly TestIssueHumanFormatter $humanFormatter;

    /**
     * Creates the formatter from mode detection and optional renderers.
     */
    public function __construct(
        AgentDetector $agentDetector,
        string $basePath,
        ?TestIssueAiFormatter $aiFormatter = null,
        ?TestIssueHumanFormatter $humanFormatter = null,
    ) {
        $pathFormatter = new TestIssuePathFormatter($basePath);
        $sourceReader = new TestIssueSourceReader();
        $typePresentation = new TestIssueTypePresentation();
        $summary = new TestIssueSummary();
        $blockIndenter = new TestIssueBlockIndenter();
        $gutter = new TestIssueGutter();

        $this->mode = $agentDetector->resolveMode();
        $this->aiFormatter = $aiFormatter ?? new TestIssueAiFormatter($pathFormatter, $sourceReader, $typePresentation, $summary, $blockIndenter);
        $this->humanFormatter = $humanFormatter ?? new TestIssueHumanFormatter($pathFormatter, $sourceReader, $typePresentation, $summary, $blockIndenter, $gutter);
    }

    /**
     * Formats a list of test issues for either human or AI consumption.
     *
     * @param list<TestIssue> $issues collected test issues
     */
    public function format(array $issues): string
    {
        if (count($issues) === 0) {
            return '';
        }

        if ($this->mode === FormatMode::AI) {
            return $this->aiFormatter->format($issues);
        }

        return $this->humanFormatter->format($issues);
    }
}

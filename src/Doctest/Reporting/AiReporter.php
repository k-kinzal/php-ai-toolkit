<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Reporting;

use PhpAiToolkit\Doctest\Config\ReportConfig;
use PhpAiToolkit\Doctest\Execution\SuiteResult;

/**
 * AI-oriented doctest reporter with explicit remediation guidance.
 *
 * @visibility namespace
 */
final class AiReporter implements Reporter
{
    /** @readonly */
    private ResultSorter $sorter;

    /** @readonly */
    private AiReportSummary $summary;

    /** @readonly */
    private AiReportGuidance $guidance;

    /** @readonly */
    private AiFailureFormatter $failureFormatter;

    /**
     * Creates an AI reporter with result ordering support.
     */
    public function __construct(
        ?ResultSorter $sorter = null,
        ?AiReportSummary $summary = null,
        ?AiReportGuidance $guidance = null,
        ?AiFailureFormatter $failureFormatter = null,
    ) {
        $this->sorter = $sorter ?? new ResultSorter();
        $this->summary = $summary ?? new AiReportSummary();
        $this->guidance = $guidance ?? new AiReportGuidance();
        $this->failureFormatter = $failureFormatter ?? new AiFailureFormatter();
    }

    /**
     * Formats a structured report intended for AI coding agents.
     */
    public function report(SuiteResult $result, ReportConfig $config): string
    {
        $output = $result->hasFailures() ? "DOCTEST_FAILED\n" : "DOCTEST_PASSED\n";
        $output .= $this->summary->summary($result);

        if (!$result->hasFailures()) {
            return $output;
        }

        $output .= $this->guidance->guidance();
        foreach ($this->sorter->sort($result->failed(), $config) as $index => $failed) {
            $output .= $this->failureFormatter->format($index + 1, $failed);
        }

        return $output;
    }
}

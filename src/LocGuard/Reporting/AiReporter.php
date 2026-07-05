<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\Analysis\AnalysisResult;
use PhpAiToolkit\LocGuard\Config\ReportConfig;

/**
 * AI-oriented LocGuard reporter with explicit remediation guidance.
 */
final class AiReporter implements Reporter
{
    /** @readonly */
    private ViolationSorter $sorter;

    /** @readonly */
    private AiReportSummary $summary;

    /** @readonly */
    private AiReportGuidance $guidance;

    /** @readonly */
    private AiViolationFormatter $violationFormatter;

    /**
     * Creates an AI reporter with violation ordering support.
     */
    public function __construct(
        ?ViolationSorter $sorter = null,
        ?AiReportSummary $summary = null,
        ?AiReportGuidance $guidance = null,
        ?AiViolationFormatter $violationFormatter = null,
    ) {
        $this->sorter = $sorter ?? new ViolationSorter();
        $this->summary = $summary ?? new AiReportSummary();
        $this->guidance = $guidance ?? new AiReportGuidance();
        $this->violationFormatter = $violationFormatter ?? new AiViolationFormatter();
    }

    /**
     * Formats a structured report intended for AI coding agents.
     */
    public function report(AnalysisResult $result, ReportConfig $config): string
    {
        $output = $result->hasViolations() ? "LOC_GUARD_FAILED\n" : "LOC_GUARD_PASSED\n";
        $output .= $this->summary->summary($result);

        if (!$result->hasViolations()) {
            return $output;
        }

        $output .= $this->guidance->guidance();
        foreach ($this->sorter->sort($result->violations, $config) as $index => $violation) {
            $output .= $this->violationFormatter->format($index + 1, $violation);
        }

        return $output;
    }
}

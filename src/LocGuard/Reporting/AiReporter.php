<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Reporting;

use function implode;

use PhpAiToolkit\LocGuard\Analysis\AnalysisResult;
use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Config\ReportConfig;

use function sprintf;

/**
 * AI-oriented LocGuard reporter with explicit remediation guidance.
 */
final class AiReporter implements Reporter
{
    /**
     * Creates an AI reporter with violation ordering support.
     */
    public function __construct(
        private readonly ViolationSorter $sorter = new ViolationSorter(),
    ) {
    }

    /**
     * Formats a structured report intended for AI coding agents.
     */
    public function report(AnalysisResult $result, ReportConfig $config): string
    {
        $output = $result->hasViolations() ? "LOC_GUARD_FAILED\n" : "LOC_GUARD_PASSED\n";
        $output .= $this->summary($result);

        if (!$result->hasViolations()) {
            return $output;
        }

        $output .= $this->guidance();
        foreach ($this->sorter->sort($result->violations, $config) as $index => $violation) {
            $output .= $this->violation($index + 1, $violation);
        }

        return $output;
    }

    private function summary(AnalysisResult $result): string
    {
        return sprintf(
            "summary:\n- files: %d\n- physical_lines: %d\n- ncloc: %d\n- violations: %d\n",
            $result->fileCount(),
            $result->physicalLineCount(),
            $result->nonCommentLineCount(),
            $result->violationCount(),
        );
    }

    private function guidance(): string
    {
        return implode("\n", [
            'guidance:',
            '- Fix listed source files directly; do not relax limits unless the project owner accepts that policy change.',
            '- For file_lines or file_ncloc, split files or move cohesive responsibilities into focused collaborators.',
            '- For class, trait, interface, or enum line violations, extract cohesive types or reduce mixed responsibilities.',
            '- For function or method line violations, extract named operations without hiding control flow in opaque helpers.',
            '- For cyclomatic_complexity, reduce independent branches with clearer dispatch, early returns, or smaller decisions.',
            'violations:',
        ]) . "\n";
    }

    private function violation(int $number, Violation $violation): string
    {
        return sprintf(
            "%d. %s:%d [%s]\n   actual: %d\n   limit: %d\n   message: %s\n   action: %s\n",
            $number,
            $violation->path,
            $violation->line,
            $violation->rule,
            $violation->actual,
            $violation->limit,
            $violation->message,
            $this->action($violation),
        );
    }

    private function action(Violation $violation): string
    {
        if ($violation->rule === 'cyclomatic_complexity') {
            return 'Reduce branch count while preserving behavior; add or update tests around the changed decisions.';
        }

        if ($violation->rule === 'file_ncloc') {
            return 'Reduce executable code in the file, not just comments or blank lines.';
        }

        if ($violation->rule === 'file_lines') {
            return 'Reduce physical file size by extracting cohesive source units.';
        }

        if ($violation->rule === 'function_lines' || $violation->rule === 'method_lines') {
            return 'Split the long function-like body into smaller named operations with clear responsibilities.';
        }

        return 'Reduce the oversized type by extracting cohesive responsibilities or narrowing its public surface.';
    }
}

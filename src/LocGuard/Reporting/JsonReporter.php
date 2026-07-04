<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Reporting;

use function array_map;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

use PhpAiToolkit\LocGuard\Analysis\AnalysisResult;
use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Config\ReportConfig;

/**
 * Machine-readable JSON LocGuard reporter.
 */
final class JsonReporter implements Reporter
{
    /**
     * Creates a JSON reporter with violation ordering support.
     */
    public function __construct(
        private readonly ViolationSorter $sorter = new ViolationSorter(),
    ) {
    }

    /**
     * Formats a JSON report for CI and machine consumers.
     */
    public function report(AnalysisResult $result, ReportConfig $config): string
    {
        $json = json_encode([
            'status' => $result->hasViolations() ? 'failed' : 'passed',
            'summary' => [
                'files' => $result->fileCount(),
                'physical_lines' => $result->physicalLineCount(),
                'ncloc' => $result->nonCommentLineCount(),
                'violations' => $result->violationCount(),
            ],
            'violations' => array_map(
                static fn (Violation $violation): array => [
                    'path' => $violation->path,
                    'line' => $violation->line,
                    'rule' => $violation->rule,
                    'actual' => $violation->actual,
                    'limit' => $violation->limit,
                    'message' => $violation->message,
                ],
                $this->sorter->sort($result->violations, $config),
            ),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return ($json === false ? '{}' : $json) . "\n";
    }
}

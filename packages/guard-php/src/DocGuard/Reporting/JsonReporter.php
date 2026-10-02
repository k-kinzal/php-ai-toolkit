<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Reporting;

use function array_map;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

use Toolkit\DocGuard\Analysis\AnalysisResult;
use Toolkit\DocGuard\Analysis\Violation;
use Toolkit\DocGuard\Config\ReportConfig;

/**
 * Machine-readable JSON DocGuard reporter.
 */
final class JsonReporter implements Reporter
{
    /** @readonly */
    private ViolationSorter $sorter;

    /**
     * Creates a JSON reporter with violation ordering support.
     */
    public function __construct(?ViolationSorter $sorter = null)
    {
        $this->sorter = $sorter ?? new ViolationSorter();
    }

    /**
     * Formats a JSON report for CI and machine consumers.
     */
    public function report(AnalysisResult $result, ReportConfig $config): string
    {
        $json = json_encode([
            'status' => $result->hasViolations() ? 'failed' : 'passed',
            'summary' => [
                'documents' => $result->documents,
                'headings' => $result->headings,
                'violations' => $result->violationCount(),
            ],
            'violations' => array_map(
                static fn (Violation $violation): array => [
                    'path' => $violation->path,
                    'line' => $violation->line,
                    'rule' => $violation->rule,
                    'expected' => $violation->expected,
                    'actual' => $violation->actual,
                    'message' => $violation->message,
                ],
                $this->sorter->sort($result->violations, $config),
            ),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return ($json === false ? '{}' : $json) . "\n";
    }
}

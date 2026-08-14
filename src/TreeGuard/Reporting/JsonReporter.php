<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Reporting;

use function array_map;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

use PhpAiToolkit\TreeGuard\Analysis\AnalysisResult;
use PhpAiToolkit\TreeGuard\Analysis\Violation;
use PhpAiToolkit\TreeGuard\Config\ReportConfig;

/**
 * Machine-readable JSON TreeGuard reporter.
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
                'directories' => $result->directoryCount(),
                'files' => $result->fileCount(),
                'violations' => $result->violationCount(),
            ],
            'violations' => array_map(
                static fn (Violation $violation): array => [
                    'path' => $violation->path,
                    'rule' => $violation->rule,
                    'pattern' => $violation->pattern,
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

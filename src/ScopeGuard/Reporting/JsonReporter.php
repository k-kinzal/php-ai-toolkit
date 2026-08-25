<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Reporting;

use function array_map;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

use Toolkit\ScopeGuard\Analysis\AnalysisResult;
use Toolkit\ScopeGuard\Analysis\Violation;
use Toolkit\ScopeGuard\Config\ReportConfig;

/**
 * Machine-readable JSON ScopeGuard reporter.
 *
 * @visibility namespace
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
                'files' => $result->fileCount,
                'scoped_declarations' => $result->scopedDeclarationCount,
                'references' => $result->referenceCount,
                'violations' => $result->violationCount(),
            ],
            'violations' => array_map(
                static fn (Violation $violation): array => [
                    'path' => $violation->path,
                    'line' => $violation->line,
                    'rule' => $violation->rule,
                    'symbol' => $violation->symbol,
                    'message' => $violation->message,
                ],
                $this->sorter->sort($result->violations, $config),
            ),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return ($json === false ? '{}' : $json) . "\n";
    }
}

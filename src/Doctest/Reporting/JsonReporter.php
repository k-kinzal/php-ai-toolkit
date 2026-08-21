<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Reporting;

use function array_map;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

use PhpAiToolkit\Doctest\Config\ReportConfig;
use PhpAiToolkit\Doctest\Execution\RunFailure;
use PhpAiToolkit\Doctest\Execution\RunResult;
use PhpAiToolkit\Doctest\Execution\SuiteResult;

/**
 * Machine-readable JSON doctest reporter.
 *
 * @visibility namespace
 */
final class JsonReporter implements Reporter
{
    /** @readonly */
    private ResultSorter $sorter;

    /**
     * Creates a JSON reporter with result ordering support.
     */
    public function __construct(?ResultSorter $sorter = null)
    {
        $this->sorter = $sorter ?? new ResultSorter();
    }

    /**
     * Formats a JSON report for CI and machine consumers.
     */
    public function report(SuiteResult $result, ReportConfig $config): string
    {
        $json = json_encode([
            'status' => $result->hasFailures() ? 'failed' : 'passed',
            'summary' => [
                'files' => $result->fileCount,
                'examples' => $result->total(),
                'passed' => $result->passedCount(),
                'failed' => $result->failedCount(),
                'skipped' => $result->skippedCount(),
            ],
            'failures' => array_map(
                fn (RunResult $failed): array => $this->failure($failed),
                $this->sorter->sort($result->failed(), $config),
            ),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return ($json === false ? '{}' : $json) . "\n";
    }

    /**
     * Returns the JSON shape of one failing example.
     *
     * @return array<string, mixed>
     */
    public function failure(RunResult $result): array
    {
        return [
            'id' => $result->example->id(),
            'path' => $result->example->target->reportPath(),
            'line' => $result->example->line,
            'symbol' => $result->example->target->symbol(),
            'assertions' => array_map(
                static fn (RunFailure $failure): array => [
                    'line' => $failure->line,
                    'code' => $failure->code,
                    'message' => $failure->message,
                    'expected' => $failure->expected,
                    'actual' => $failure->actual,
                ],
                $result->failures,
            ),
        ];
    }
}

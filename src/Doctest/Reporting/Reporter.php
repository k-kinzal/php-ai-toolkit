<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Reporting;

use PhpAiToolkit\Doctest\Config\ReportConfig;
use PhpAiToolkit\Doctest\Execution\SuiteResult;

/**
 * Formats a doctest run result for one output target.
 */
interface Reporter
{
    /**
     * Formats the run result using the configured output order.
     */
    public function report(SuiteResult $result, ReportConfig $config): string;
}

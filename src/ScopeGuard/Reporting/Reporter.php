<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Reporting;

use PhpAiToolkit\ScopeGuard\Analysis\AnalysisResult;
use PhpAiToolkit\ScopeGuard\Config\ReportConfig;

/**
 * Formats a ScopeGuard analysis result for one output target.
 */
interface Reporter
{
    /**
     * Formats the analysis result using the configured output order.
     */
    public function report(AnalysisResult $result, ReportConfig $config): string;
}

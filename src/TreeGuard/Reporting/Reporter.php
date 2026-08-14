<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Reporting;

use PhpAiToolkit\TreeGuard\Analysis\AnalysisResult;
use PhpAiToolkit\TreeGuard\Config\ReportConfig;

/**
 * Formats a TreeGuard analysis result for one output target.
 */
interface Reporter
{
    /**
     * Formats the analysis result using the configured output order.
     */
    public function report(AnalysisResult $result, ReportConfig $config): string;
}

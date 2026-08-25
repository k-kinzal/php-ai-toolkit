<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Reporting;

use Toolkit\ScopeGuard\Analysis\AnalysisResult;
use Toolkit\ScopeGuard\Config\ReportConfig;

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

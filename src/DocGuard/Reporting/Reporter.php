<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Reporting;

use Toolkit\DocGuard\Analysis\AnalysisResult;
use Toolkit\DocGuard\Config\ReportConfig;

/**
 * Formats a DocGuard analysis result for one output target.
 */
interface Reporter
{
    /**
     * Formats the analysis result using the configured output order.
     */
    public function report(AnalysisResult $result, ReportConfig $config): string;
}

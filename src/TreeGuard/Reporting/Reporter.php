<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Reporting;

use Toolkit\TreeGuard\Analysis\AnalysisResult;
use Toolkit\TreeGuard\Config\ReportConfig;

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

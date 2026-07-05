<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Reporting;

use function implode;

/**
 * Provides remediation guidance for AI LocGuard reports.
 */
final class AiReportGuidance
{
    /**
     * Returns the static guidance block.
     */
    public function guidance(): string
    {
        return implode("\n", [
            'guidance:',
            '- Fix listed source files directly; do not relax limits unless the project owner accepts that policy change.',
            '- For file_lines or file_ncloc, split files or move cohesive responsibilities into focused collaborators.',
            '- For class, trait, interface, or enum line violations, extract cohesive types or reduce mixed responsibilities.',
            '- For function or method line violations, extract named operations without hiding control flow in opaque helpers.',
            '- For cyclomatic_complexity, reduce independent branches with clearer dispatch, early returns, or smaller decisions.',
            'violations:',
        ]) . "\n";
    }
}

<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Reporting;

use function implode;

/**
 * Provides remediation guidance for AI TreeGuard reports.
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
            '- Fix the listed structure directly; do not relax tree.yaml limits unless the project owner accepts that policy change.',
            '- For max_files or max_dirs, group cohesive files into focused subdirectories instead of renaming arbitrarily.',
            '- For max_total_files or max_depth, restructure the subtree; consider splitting it into separate packages.',
            '- For naming violations, rename the entry to the configured convention or move it to a directory that allows it.',
            '- For missing required files, create each file with its intended content.',
            '- For empty directories, delete the directory or add its intended contents.',
            'violations:',
        ]) . "\n";
    }
}

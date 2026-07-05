<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\Analysis\Violation;

/**
 * Selects remediation actions for individual LocGuard violations.
 */
final class AiViolationAction
{
    /**
     * Returns an action message for the violation rule.
     */
    public function action(Violation $violation): string
    {
        if ($violation->rule === 'cyclomatic_complexity') {
            return 'Reduce branch count while preserving behavior; add or update tests around the changed decisions.';
        }

        if ($violation->rule === 'file_ncloc') {
            return 'Reduce executable code in the file, not just comments or blank lines.';
        }

        if ($violation->rule === 'file_lines') {
            return 'Reduce physical file size by extracting cohesive source units.';
        }

        if ($violation->rule === 'function_lines' || $violation->rule === 'method_lines') {
            return 'Split the long function-like body into smaller named operations with clear responsibilities.';
        }

        return 'Reduce the oversized type by extracting cohesive responsibilities or narrowing its public surface.';
    }
}

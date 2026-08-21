<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Reporting;

use function implode;

/**
 * Provides remediation guidance for AI ScopeGuard reports.
 *
 * @visibility namespace
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
            '- Fix the listed code directly; do not delete an @visibility tag or widen scope.yaml to silence a violation unless the project owner accepts that design change.',
            '- For out_of_scope, prefer moving the referencing code into the namespace that owns the declaration.',
            '- When several namespaces need the same declaration, that is a missing entry point: export one public type from the owning namespace instead of widening the scope.',
            '- Widen the tag only when the scope was drawn too narrowly; each message names the narrowest scope that would admit the reference.',
            '- For invalid_scope, rewrite the tag; a scope that resolves to nothing restricts nothing.',
            'violations:',
        ]) . "\n";
    }
}

<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Reporting;

use function implode;

/**
 * Provides remediation guidance for AI doctest reports.
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
            '- A failing example means the documentation and the code disagree; decide which one is wrong before editing either.',
            '- Fix the code when the example documents the behaviour the callers were promised.',
            '- Fix the example when the code is right and the documentation went stale; keep the assertion, do not delete it.',
            '- Deleting an example or dropping its "// =>", "// Output:", or "// throws" marker is not a fix: it removes the check instead of satisfying it.',
            '- Run one example on its own with: vendor/bin/doctest --filter=<id>',
            'failures:',
        ]) . "\n";
    }
}

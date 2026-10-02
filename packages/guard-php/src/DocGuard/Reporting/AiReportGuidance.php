<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Reporting;

use function implode;

/**
 * Provides remediation guidance for AI DocGuard reports.
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
            '- The heading structure of each document is declared in the DocGuard config, which a human maintains. Do not edit that file and do not regenerate it with doc-guard --generate.',
            '- Edit the content inside existing sections freely. Headings, their levels, their order, and the set of documents are fixed.',
            '- Do not add sections or documents. Write new information inside the existing section that covers the topic.',
            '- If the change really needs a different structure, keep the declared structure and ask a human to update the DocGuard config.',
            'violations:',
        ]) . "\n";
    }
}

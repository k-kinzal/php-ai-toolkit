<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Reporting;

use Toolkit\DocGuard\Analysis\Violation;

/**
 * Selects remediation actions for individual DocGuard violations.
 */
final class AiViolationAction
{
    /** @var array<string, string> */
    private const ACTIONS = [
        'unexpected_heading' => 'Do not add a section. Delete this heading and write its content inside the existing section that covers the topic. If a new section is really needed, ask a human to update the DocGuard config.',
        'missing_heading' => 'Restore the declared heading at its declared position and keep its content there. If the section should really be removed, ask a human to update the DocGuard config.',
        'renamed_heading' => 'Restore the declared heading text and edit only the content below it. If the section should really be renamed, ask a human to update the DocGuard config.',
        'changed_heading_level' => 'Restore the declared heading level so the section nests as declared. If the nesting should really change, ask a human to update the DocGuard config.',
        'moved_heading' => 'Move the section back to its declared position. If the order should really change, ask a human to update the DocGuard config.',
        'missing_document' => 'Restore the document at its declared path. If the document should really be removed or renamed, ask a human to update the DocGuard config.',
        'undeclared_document' => 'Do not add a document. Move its content into an existing section of a declared document and delete this file. If a new document is really needed, ask a human to declare it in the DocGuard config.',
    ];

    /**
     * Returns an action message for the violation rule.
     */
    public function action(Violation $violation): string
    {
        return self::ACTIONS[$violation->rule] ?? 'Restore the declared document structure, or ask a human to update the DocGuard config.';
    }
}

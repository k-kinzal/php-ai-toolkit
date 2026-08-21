<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Reporting;

use PhpAiToolkit\ScopeGuard\Analysis\Violation;

/**
 * Selects remediation actions for individual ScopeGuard violations.
 *
 * @visibility namespace
 */
final class AiViolationAction
{
    /** @var array<string, string> */
    private const ACTIONS = [
        'out_of_scope' => 'Move the referencing code into the namespace that owns the declaration, export a public entry point from that namespace, or widen the @visibility tag to the scope named in the message.',
        'invalid_scope' => 'Rewrite the @visibility tag so it names a scope that can be honoured: "public", "root", "parent", "namespace", or a namespace name.',
    ];

    /**
     * Returns an action message for the violation rule.
     */
    public function action(Violation $violation): string
    {
        return self::ACTIONS[$violation->rule] ?? 'Make the code satisfy the declared visibility scope.';
    }
}

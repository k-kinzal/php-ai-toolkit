<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Reporting;

use PhpAiToolkit\ScopeGuard\ScopeGuardException;

use function sprintf;

/**
 * Creates ScopeGuard reporters from configuration names.
 */
final class ReporterFactory
{
    /**
     * Creates the configured reporter.
     *
     * @throws ScopeGuardException when the reporter name is not one of: ai, text, json
     */
    public function create(string $reporter): Reporter
    {
        if ($reporter === 'ai') {
            return new AiReporter();
        }

        if ($reporter === 'text') {
            return new TextReporter();
        }

        if ($reporter === 'json') {
            return new JsonReporter();
        }

        throw new ScopeGuardException(sprintf('Unknown ScopeGuard reporter: %s', $reporter));
    }
}

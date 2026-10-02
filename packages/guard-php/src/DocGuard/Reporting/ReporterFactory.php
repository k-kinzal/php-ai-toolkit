<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Reporting;

use function sprintf;

use Toolkit\DocGuard\DocGuardException;

/**
 * Creates DocGuard reporters from configuration names.
 */
final class ReporterFactory
{
    /**
     * Creates the configured reporter.
     *
     * @throws DocGuardException when the reporter name is not one of: ai, text, json
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

        throw new DocGuardException(sprintf('Unknown DocGuard reporter: %s. Use one of: ai, text, json.', $reporter));
    }
}

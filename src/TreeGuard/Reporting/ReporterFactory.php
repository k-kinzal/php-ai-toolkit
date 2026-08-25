<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Reporting;

use function sprintf;

use Toolkit\TreeGuard\TreeGuardException;

/**
 * Creates TreeGuard reporters from configuration names.
 */
final class ReporterFactory
{
    /**
     * Creates the configured reporter.
     *
     * @throws TreeGuardException when the reporter name is not one of: ai, text, json
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

        throw new TreeGuardException(sprintf('Unknown TreeGuard reporter: %s', $reporter));
    }
}

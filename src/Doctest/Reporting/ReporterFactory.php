<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Reporting;

use PhpAiToolkit\Doctest\DoctestException;

use function sprintf;

/**
 * Creates doctest reporters from configuration names.
 */
final class ReporterFactory
{
    /**
     * Creates the configured reporter.
     *
     * @throws DoctestException when the reporter name is not one of: ai, text, json
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

        throw new DoctestException(sprintf('Unknown doctest reporter: %s', $reporter));
    }
}

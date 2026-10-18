<?php

declare(strict_types=1);

namespace DecisionsLint\Reporting;

use DecisionsLint\Diagnostic\LintException;

/**
 * Picks the reporter of an output format name.
 */
final class ReporterFactory
{
    /**
     * The formats --format accepts; human is an alias of text.
     *
     * @var list<string>
     */
    public const FORMATS = ['text', 'human', 'ai', 'json', 'checkstyle', 'github'];

    /**
     * Returns the reporter for a format.
     *
     * @throws LintException when the format is not one of FORMATS
     */
    public function create(string $format): Reporter
    {
        return match ($format) {
            'text', 'human' => new TextReporter(),
            'ai' => new AiReporter(),
            'json' => new JsonReporter(),
            'checkstyle' => new CheckstyleReporter(),
            'github' => new GithubReporter(),
            default => throw new LintException(sprintf('Unsupported --format "%s". Use --format=%s.', $format, implode(', ', self::FORMATS))),
        };
    }
}

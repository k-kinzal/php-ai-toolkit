<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Cli;

use function implode;

/**
 * Provides the long help of the docgen command.
 *
 * The options describe themselves in the option list Symfony Console
 * prints; this text says what the run as a whole does and how it ends.
 */
final class DocGenHelpText
{
    /**
     * Returns the complete help text.
     */
    public function text(): string
    {
        return implode("\n\n", [$this->purpose(), $this->examples(), $this->exitCodes()]);
    }

    /**
     * Returns the opening paragraphs that state what docgen does.
     */
    public function purpose(): string
    {
        return <<<'TEXT'
Generates a static HTML documentation site for the composer packages of the
current project. Everything is named on the command line: without options,
the project root and packages/* are documented into build/docs.

Options that take globs accept a comma-separated list and may be repeated,
which adds to what the earlier occurrences named.
TEXT;
    }

    /**
     * Returns the example command lines.
     */
    public function examples(): string
    {
        return <<<'TEXT'
Examples:
  docgen --exclude=tests/Fixture/*        document the project into build/docs
  docgen --vendor=acme/* -o public/docs   also document acme packages
  docgen --diff=main                      mark what changed since main
  docgen --serve=8080                     generate, then preview the site
TEXT;
    }

    /**
     * Returns the exit codes a run ends with.
     */
    public function exitCodes(): string
    {
        return <<<'TEXT'
Exit codes:
  0  documentation generated
  2  invalid command line, configuration, or runtime error
TEXT;
    }
}

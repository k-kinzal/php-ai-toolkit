<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Cli;

/**
 * Provides DocGuard CLI help text.
 */
final class DocGuardHelpText
{
    /**
     * Returns the CLI help text.
     */
    public function text(): string
    {
        return <<<'TEXT'
doc-guard checks that Markdown documents keep their declared section structure.

Usage:
  doc-guard [--config=doc-guard.yaml] [--reporter=ai|text|json]
  doc-guard --generate [PATH...]

Options:
  --config PATH       Path to doc-guard.yaml (default: doc-guard.yaml)
  --reporter NAME     Reporter: ai, text, or json
  --format NAME       Alias of --reporter
  --generate          Print a doc-guard.yaml declaring the current structure of the
                      given Markdown files and directories (default: *.md and
                      docs/**/*.md in the working directory)
  --help, -h          Show this help message
  --version, -V       Show version

TEXT;
    }
}

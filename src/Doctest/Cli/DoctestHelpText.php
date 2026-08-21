<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Cli;

/**
 * Provides doctest CLI help text.
 *
 * @visibility namespace
 */
final class DoctestHelpText
{
    /**
     * Returns the CLI help text.
     */
    public function text(): string
    {
        return <<<'TEXT'
doctest runs the examples written in PHPDoc blocks.

Usage:
  doctest [--config=doctest.yaml] [--filter=ID] [--list] [--reporter=ai|text|json]

Options:
  --config PATH       Path to doctest.yaml (default: doctest.yaml)
  --filter ID         Run only examples whose identifier or file path contains ID
  --list              Print the identifier of every selected example without running it
  --reporter NAME     Reporter: ai, text, or json
  --format NAME       Alias of --reporter
  --help, -h          Show this help message
  --version, -V       Show version

Examples:
  doctest
  doctest --list
  doctest --filter='App\Billing\Ledger::append()#1'

TEXT;
    }
}

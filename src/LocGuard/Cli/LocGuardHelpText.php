<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Cli;

/**
 * Provides LocGuard CLI help text.
 */
final class LocGuardHelpText
{
    /**
     * Returns the CLI help text.
     */
    public function text(): string
    {
        return <<<'TEXT'
loc-guard checks PHP source line-count and complexity thresholds.

Usage:
  loc-guard [--config=loc.yaml] [--reporter=ai|text|json]

Options:
  --config PATH       Path to loc.yaml (default: loc.yaml)
  --reporter NAME     Reporter: ai, text, or json
  --format NAME       Alias of --reporter
  --help, -h          Show this help message
  --version, -V       Show version

TEXT;
    }
}

<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Cli;

/**
 * Provides ScopeGuard CLI help text.
 *
 * @visibility namespace
 */
final class ScopeGuardHelpText
{
    /**
     * Returns the CLI help text.
     */
    public function text(): string
    {
        return <<<'TEXT'
scope-guard checks the namespace visibility scopes declared with @visibility.

Usage:
  scope-guard [--config=scope.yaml] [--reporter=ai|text|json]

Options:
  --config PATH       Path to scope.yaml (default: scope.yaml)
  --reporter NAME     Reporter: ai, text, or json
  --format NAME       Alias of --reporter
  --help, -h          Show this help message
  --version, -V       Show version

TEXT;
    }
}

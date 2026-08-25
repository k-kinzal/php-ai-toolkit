<?php

declare(strict_types=1);

namespace Toolkit\TreeGuard\Cli;

/**
 * Provides TreeGuard CLI help text.
 */
final class TreeGuardHelpText
{
    /**
     * Returns the CLI help text.
     */
    public function text(): string
    {
        return <<<'TEXT'
tree-guard checks directory and file structure constraints.

Usage:
  tree-guard [--config=tree.yaml] [--reporter=ai|text|json]

Options:
  --config PATH       Path to tree.yaml (default: tree.yaml)
  --reporter NAME     Reporter: ai, text, or json
  --format NAME       Alias of --reporter
  --help, -h          Show this help message
  --version, -V       Show version

TEXT;
    }
}

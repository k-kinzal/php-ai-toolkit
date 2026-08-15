<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Cli;

/**
 * Provides the doc-gen CLI usage text.
 */
final class DocGenHelpText
{
    /**
     * Returns the complete usage text.
     */
    public function text(): string
    {
        return <<<'TEXT'
Usage: doc-gen [options]

Generates a static HTML documentation site for the composer packages of the
current project. Without a config file, the project root and packages/* are
documented into build/docs.

Options:
  --config=FILE      Configuration file (default: doc.yaml when present)
  --output=DIR       Output directory (default: build/docs)
  --vendor[=GLOBS]   Also document installed runtime (non-dev) vendor packages
                     whose composer package name matches one of the
                     comma-separated globs, such as --vendor=acme/*
                     (bare --vendor means all runtime dependencies)
  --vendor-dev[=GLOBS]
                     Same for installed dev dependencies, such as
                     --vendor-dev=phpunit/* (bare --vendor-dev means all dev
                     dependencies); combine it with --vendor to document both
  --coverage=DIR     PHPUnit --coverage-xml report directory; links methods
                     to the test cases that cover them
  --serve[=ADDR]     Serve the generated site locally after generation
                     (default address: 127.0.0.1:8090)
  --memory-limit=X   Memory limit for the run, such as 1G or -1 (default:
                     the environment limit, raised to 512M when it is lower)
  -h, --help         Show this help
  -V, --version      Show the version

Exit codes:
  0  documentation generated
  2  configuration or runtime error

TEXT;
    }
}

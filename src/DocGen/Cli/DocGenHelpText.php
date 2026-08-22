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
        return $this->purpose() . $this->scopeOptions() . $this->siteOptions() . $this->diffOptions() . $this->cacheOptions() . $this->runOptions();
    }

    /**
     * Returns the opening lines that state what doc-gen does.
     */
    public function purpose(): string
    {
        return <<<'TEXT'
Usage: doc-gen [options]

Generates a static HTML documentation site for the composer packages of the
current project. Everything is named on the command line: without options,
the project root and packages/* are documented into build/docs.

Options that take GLOBS accept a comma-separated list and may be repeated,
which adds to what the earlier occurrences named.

Options:

TEXT;
    }

    /**
     * Returns the options that decide what is documented.
     */
    public function scopeOptions(): string
    {
        return <<<'TEXT'
  --packages=GLOBS   Directory globs probed for a composer.json; every
                     directory that has one becomes a documented package
                     (default: . and packages/*)
  --exclude=GLOBS    Path globs, relative to the project root, pruned from
                     source scanning, such as --exclude=tests/Fixture/*
  --vendor[=GLOBS]   Also document installed runtime (non-dev) vendor packages
                     whose composer package name matches one of the
                     comma-separated globs, such as --vendor=acme/*
                     (bare --vendor means all runtime dependencies)
  --vendor-dev[=GLOBS]
                     Same for installed dev dependencies, such as
                     --vendor-dev=phpunit/* (bare --vendor-dev means all dev
                     dependencies); combine it with --vendor to document both
  --output=DIR       Output directory (default: build/docs)
  --title=TEXT       Site title (default: the name of the root package, else
                     the name of the project directory)

TEXT;
    }

    /**
     * Returns the options that decide what the pages say about the project.
     */
    public function siteOptions(): string
    {
        return <<<'TEXT'
  --deptrac=FILE     Deptrac configuration the architecture graph and the
                     per-class layer badges are read from (default:
                     deptrac.yaml at the project root when it exists)
  --coverage=DIR     PHPUnit --coverage-xml report directory; links methods
                     to the test cases that cover them
  --base-url=URL     Address the site is published at, such as
                     https://example.github.io/project; every page then
                     carries its canonical link and the social preview
                     tags a link shared elsewhere is rendered from
  --repository=URL   Address of the repository the documented code lives in,
                     which every page links back to (default: what the root
                     package declares in support.source, then homepage)

TEXT;
    }

    /**
     * Returns the options that compare two revisions of the project.
     */
    public function diffOptions(): string
    {
        return <<<'TEXT'
  --diff=RANGE       Compare two git revisions: BASE compares the working
                     tree against BASE, BASE..HEAD compares two revisions.
                     Every page then marks what was added and removed, and
                     the site offers three display modes: the plain
                     documentation, the marked documentation, and the
                     changes alone
  --base=REVISION    Base revision of the comparison, as --diff=REVISION
  --head=REVISION    Head revision of the comparison (default: the working
                     tree); requires --base

TEXT;
    }

    /**
     * Returns the options that decide what is remembered between runs.
     */
    public function cacheOptions(): string
    {
        return <<<'TEXT'
  --cache-dir=DIR    Directory the parsed sources and the written pages are
                     remembered in, so the next run only parses what
                     changed and only rewrites the pages that changed
                     (default: build/doc-gen-cache)
  --no-cache         Parse every source and write every page, and remember
                     nothing of it
  --clear-cache      Remove the cache directory before generating

TEXT;
    }

    /**
     * Returns the options that decide how one run itself is carried out.
     */
    public function runOptions(): string
    {
        return <<<'TEXT'
  --serve[=ADDR]     Serve the generated site locally after generation
                     (default address: 127.0.0.1:8090)
  --memory-limit=X   Memory limit for the run, such as 1G or -1 (default:
                     the environment limit, raised to 512M when it is lower)
  --jobs=N           Analyze and render with N worker processes (default: one
                     per CPU core, minus one, at most 16). Use --jobs=1 to
                     stay in one process. Workers need the pcntl extension
                     and are not used while OPcache or the JIT is on; the
                     generated site is the same either way
  -h, --help         Show this help
  -V, --version      Show the version

Exit codes:
  0  documentation generated
  2  configuration or runtime error

TEXT;
    }
}

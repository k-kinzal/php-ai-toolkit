<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Cli;

use PhpAiToolkit\DocGen\Config\DocGenConfig;

use function realpath;

/**
 * Builds the configuration of one run out of its command line.
 *
 * The options of a run are the whole configuration: what a command does not
 * name keeps the default, and the documented project is the directory the
 * command was run in. Nothing is read from a file, so a run documents what
 * its caller asked for rather than what a checked-in file happened to say,
 * which is what lets a continuous integration job pass the values only it
 * knows, such as the address the site is published at.
 */
final class DocGenConfigFactory
{
    /**
     * Returns the configuration the arguments of one run describe.
     *
     * @param array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, noCache: bool, clearCache: bool, help: bool, version: bool} $arguments
     */
    public function create(string $workingDirectory, array $arguments): DocGenConfig
    {
        $root = realpath($workingDirectory);

        return new DocGenConfig(
            $root === false ? $workingDirectory : $root,
            $arguments['packages'] ?? DocGenConfig::DEFAULT_PACKAGES,
            $arguments['vendor'] ?? [],
            $arguments['exclude'] ?? [],
            $arguments['output'] ?? DocGenConfig::DEFAULT_OUTPUT,
            $arguments['title'],
            $arguments['deptrac'],
            $arguments['coverage'],
            $arguments['vendorDev'] ?? [],
            $this->cache($arguments),
            $arguments['baseUrl'],
            $arguments['repository'],
        );
    }

    /**
     * Returns the directory a run keeps its caches in, if any.
     *
     * A run that was told to cache nothing caches nothing, whichever
     * directory it would otherwise have kept its caches in.
     *
     * @param array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, noCache: bool, clearCache: bool, help: bool, version: bool} $arguments
     */
    public function cache(array $arguments): ?string
    {
        if ($arguments['noCache']) {
            return null;
        }

        return $this->cacheDirectory($arguments);
    }

    /**
     * Returns the cache directory of a run, whether it caches or clears.
     *
     * A run that caches nothing still names the directory it clears, because
     * --clear-cache and --no-cache are asked for together by exactly the run
     * that wants to prove the site does not depend on what was remembered.
     *
     * @param array{packages: ?list<string>, vendor: ?list<string>, vendorDev: ?list<string>, exclude: ?list<string>, output: ?string, title: ?string, deptrac: ?string, coverage: ?string, cacheDir: ?string, baseUrl: ?string, repository: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, noCache: bool, clearCache: bool, help: bool, version: bool} $arguments
     */
    public function cacheDirectory(array $arguments): string
    {
        return $arguments['cacheDir'] ?? DocGenConfig::DEFAULT_CACHE;
    }
}

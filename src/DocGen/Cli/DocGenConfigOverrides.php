<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Cli;

use function array_merge;

use PhpAiToolkit\DocGen\Config\DocGenConfig;

/**
 * Applies command line overrides on top of the loaded configuration.
 */
final class DocGenConfigOverrides
{
    /**
     * Returns the cache directory a run keeps its caches in, if any.
     *
     * A run that was told to cache nothing caches nothing, whatever the
     * project configured, because that is what the option is for.
     *
     * @param array{config: ?string, output: ?string, vendor: ?list<string>, vendorDev: ?list<string>, coverage: ?string, baseUrl: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, cacheDir: ?string, noCache: bool, clearCache: bool, help: bool, version: bool} $arguments
     */
    public function cache(DocGenConfig $config, array $arguments): ?string
    {
        if ($arguments['noCache']) {
            return null;
        }

        return $arguments['cacheDir'] ?? $config->cache;
    }

    /**
     * Rebuilds the configuration with the given CLI overrides applied.
     *
     * @param array{config: ?string, output: ?string, vendor: ?list<string>, vendorDev: ?list<string>, coverage: ?string, baseUrl: ?string, serve: ?string, memoryLimit: ?string, jobs: ?int, base: ?string, head: ?string, cacheDir: ?string, noCache: bool, clearCache: bool, help: bool, version: bool} $arguments
     */
    public function apply(DocGenConfig $config, array $arguments): DocGenConfig
    {
        return new DocGenConfig(
            $config->root,
            $config->packages,
            $arguments['vendor'] !== null ? array_merge($config->vendor, $arguments['vendor']) : $config->vendor,
            $config->exclude,
            $arguments['output'] ?? $config->output,
            $config->title,
            $config->deptrac,
            $arguments['coverage'] ?? $config->coverage,
            $arguments['vendorDev'] !== null ? array_merge($config->vendorDev, $arguments['vendorDev']) : $config->vendorDev,
            $this->cache($config, $arguments),
            $arguments['baseUrl'] ?? $config->baseUrl,
        );
    }
}

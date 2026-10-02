<?php

declare(strict_types=1);

/**
 * Configuration module for doctest.
 *
 * @example File-level example: creating config with defaults
 *     $config = new \Toolkit\Doctest\Configuration\Configuration();
 *     $config->isEnabled() // => true
 *     $config->getDirectories() // => []
 */

namespace Toolkit\Doctest\Configuration;

use function str_starts_with;

/**
 * Holds configuration settings for doctest execution.
 *
 * Configuration specifies which directories and files to scan for doctest
 * examples, patterns to exclude, and optional bootstrap file.
 *
 * @example Creating a basic configuration
 *     $config = new \Toolkit\Doctest\Configuration\Configuration(
 *         directories: ['/app/src'],
 *     );
 *     $config->getDirectories() // => ['/app/src']
 *     $config->isEnabled() // => true
 *
 * @example Configuration with exclusions
 *     $config = new \Toolkit\Doctest\Configuration\Configuration(
 *         directories: ['/app/src'],
 *         excludePatterns: ['*Test.php', '*Interface.php'],
 *     );
 *     count($config->getExcludePatterns()) // => 2
 */
final class Configuration
{
    /**
     * @param list<string> $directories directories to scan for PHP files
     * @param list<string> $files individual files to scan
     * @param list<string> $excludePatterns glob patterns to exclude
     * @param string|null $bootstrap path to a bootstrap file to require before tests
     * @param bool $enabled whether doctest is enabled
     */
    public function __construct(
        /** @readonly */
        private array $directories = [],
        /** @readonly */
        private array $files = [],
        /** @readonly */
        private array $excludePatterns = [],
        /** @readonly */
        private ?string $bootstrap = null,
        /** @readonly */
        private bool $enabled = true,
    ) {
    }

    /**
     * Returns the list of directories to scan.
     *
     * @return list<string>
     *
     * @example Getting directories
     *     $config = new \Toolkit\Doctest\Configuration\Configuration(directories: ['/src', '/lib']);
     *     $config->getDirectories() // => ['/src', '/lib']
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * Returns the list of individual files to scan.
     *
     * @return list<string>
     *
     * @example Getting files
     *     $config = new \Toolkit\Doctest\Configuration\Configuration(files: ['/app/helpers.php']);
     *     $config->getFiles() // => ['/app/helpers.php']
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Returns the list of exclude patterns.
     *
     * @return list<string>
     *
     * @example Getting exclude patterns
     *     $config = new \Toolkit\Doctest\Configuration\Configuration(excludePatterns: ['*Test.php']);
     *     $config->getExcludePatterns() // => ['*Test.php']
     */
    public function getExcludePatterns(): array
    {
        return $this->excludePatterns;
    }

    /**
     * Returns the path to the bootstrap file.
     *
     * @example Getting bootstrap when set
     *     $config = new \Toolkit\Doctest\Configuration\Configuration(bootstrap: '/app/bootstrap.php');
     *     $config->getBootstrap() // => '/app/bootstrap.php'
     *
     * @example Getting bootstrap when not set
     *     $config = new \Toolkit\Doctest\Configuration\Configuration();
     *     $config->getBootstrap() // => null
     */
    public function getBootstrap(): ?string
    {
        return $this->bootstrap;
    }

    /**
     * Returns whether doctest is enabled.
     *
     * @example Checking enabled state
     *     $config = new \Toolkit\Doctest\Configuration\Configuration(enabled: false);
     *     $config->isEnabled() // => false
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Returns whether the configuration selects anything to scan.
     */
    public function hasSources(): bool
    {
        return $this->directories !== [] || $this->files !== [];
    }

    /**
     * Resolves one configured path against a base directory.
     */
    public static function resolvePath(string $path, string $basePath): string
    {
        if ($basePath === '' || str_starts_with($path, '/')) {
            return $path;
        }

        return rtrim($basePath, '/') . '/' . ltrim($path, '/');
    }
}

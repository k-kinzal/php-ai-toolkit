<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Config;

use function str_starts_with;

/**
 * What a doctest run scans, and what it loads before it starts.
 *
 * Configuration is a value built by the test case that runs the examples, not a
 * file of its own: doctest is a set of PHPUnit test cases, so it is configured
 * where the rest of the suite is configured.
 *
 * @property-read string $root
 * @property-read list<string> $paths
 * @property-read list<string> $exclude
 * @property-read ?string $bootstrap
 */
final class DoctestConfig
{
    /**
     * @param string $root the directory the paths and the bootstrap are relative to
     * @param list<string> $paths the files and directories to scan for examples
     * @param list<string> $exclude fnmatch globs of project-relative paths to skip
     * @param string|null $bootstrap a file to include once before the first example
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private array $paths = ['src'],
        /** @readonly */
        private array $exclude = [],
        /** @readonly */
        private ?string $bootstrap = null,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'root' => $this->root,
            'paths' => $this->paths,
            'exclude' => $this->exclude,
            'bootstrap' => $this->bootstrap,
            default => null,
        };
    }

    /**
     * Returns the bootstrap file as an absolute path, or null when there is none.
     */
    public function bootstrapPath(): ?string
    {
        if ($this->bootstrap === null) {
            return null;
        }

        return str_starts_with($this->bootstrap, '/') ? $this->bootstrap : $this->root . '/' . $this->bootstrap;
    }
}

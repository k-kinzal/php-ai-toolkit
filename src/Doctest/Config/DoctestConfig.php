<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Config;

/**
 * Fully resolved doctest configuration.
 *
 * @property-read string $root
 * @property-read list<string> $paths
 * @property-read list<string> $exclude
 * @property-read ?string $bootstrap
 * @property-read ReportConfig $report
 */
final class DoctestConfig
{
    /**
     * @param string $root the directory the configured paths are relative to
     * @param list<string> $paths the files and directories to scan for examples
     * @param list<string> $exclude fnmatch globs of project-relative paths to skip
     * @param string|null $bootstrap a file to include once before the first example
     * @param ReportConfig $report how results are formatted and ordered
     */
    public function __construct(
        /** @readonly */
        private string $root,
        /** @readonly */
        private array $paths,
        /** @readonly */
        private array $exclude,
        /** @readonly */
        private ?string $bootstrap,
        /** @readonly */
        private ReportConfig $report,
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
            'report' => $this->report,
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

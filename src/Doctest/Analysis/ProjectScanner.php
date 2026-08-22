<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Analysis;

use function array_merge;

use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PhpAiToolkit\Doctest\DoctestException;
use PhpAiToolkit\Doctest\Filesystem\PhpFileFinder;

/**
 * Finds every example a configuration selects, across every scanned file.
 *
 * Discovery is separate from execution because a PHPUnit data provider has to
 * name all the examples before any of them runs.
 */
final class ProjectScanner
{
    /** @readonly */
    private PhpFileFinder $fileFinder;

    /** @readonly */
    private ExampleCollector $collector;

    /**
     * Creates a scanner from file discovery and per-file example collection.
     */
    public function __construct(?PhpFileFinder $fileFinder = null, ?ExampleCollector $collector = null)
    {
        $this->fileFinder = $fileFinder ?? new PhpFileFinder();
        $this->collector = $collector ?? new ExampleCollector();
    }

    /**
     * Returns every example the configuration selects, in scan order.
     *
     * @return list<Example>
     *
     * @throws DoctestException when a configured path is missing or a source file cannot be parsed
     */
    public function examples(DoctestConfig $config): array
    {
        return $this->examplesIn($this->fileFinder->find($config));
    }

    /**
     * Collects the examples of every given file.
     *
     * @param array<string, string> $files readable paths mapped to the paths reports name them by
     * @return list<Example>
     *
     * @throws DoctestException when a source file cannot be read or parsed
     */
    public function examplesIn(array $files): array
    {
        $examples = [];
        foreach ($files as $absolutePath => $relativePath) {
            $examples = array_merge($examples, $this->collector->collect($absolutePath, $relativePath));
        }

        return $examples;
    }
}

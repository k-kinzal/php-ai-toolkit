<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Execution;

use function array_merge;
use function count;

use PhpAiToolkit\Doctest\Analysis\Example;
use PhpAiToolkit\Doctest\Analysis\ExampleCollector;
use PhpAiToolkit\Doctest\Analysis\ExampleFilter;
use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PhpAiToolkit\Doctest\DoctestException;
use PhpAiToolkit\Doctest\Filesystem\PhpFileFinder;

/**
 * Runs every example a configuration selects.
 *
 * Discovery and execution are separate steps so that the same example list can
 * be printed without running anything, which is what makes a single example
 * addressable before it is run.
 */
final class SuiteRunner
{
    /** @readonly */
    private PhpFileFinder $fileFinder;

    /** @readonly */
    private ExampleCollector $collector;

    /** @readonly */
    private ExampleFilter $filter;

    /** @readonly */
    private ExampleRunner $runner;

    /**
     * Creates a suite runner from file discovery, example collection, filtering, and execution.
     */
    public function __construct(
        ?PhpFileFinder $fileFinder = null,
        ?ExampleCollector $collector = null,
        ?ExampleFilter $filter = null,
        ?ExampleRunner $runner = null,
    ) {
        $this->fileFinder = $fileFinder ?? new PhpFileFinder();
        $this->collector = $collector ?? new ExampleCollector();
        $this->filter = $filter ?? new ExampleFilter();
        $this->runner = $runner ?? new ExampleRunner();
    }

    /**
     * Returns every example the configuration selects, in scan order.
     *
     * @return list<Example>
     *
     * @throws DoctestException when a configured path is missing or a source file cannot be parsed
     */
    public function collect(DoctestConfig $config, ?string $filter = null): array
    {
        return $this->filter->apply($this->collectFrom($this->fileFinder->find($config)), $filter);
    }

    /**
     * Runs every selected example and returns the aggregated result.
     *
     * @throws DoctestException when a configured path is missing or a source file cannot be read
     */
    public function run(DoctestConfig $config, ?string $filter = null): SuiteResult
    {
        $files = $this->fileFinder->find($config);
        $examples = $this->filter->apply($this->collectFrom($files), $filter);
        $bootstrap = $config->bootstrapPath();

        $results = [];
        foreach ($examples as $example) {
            $results[] = $this->runner->run($example, $bootstrap);
        }

        return new SuiteResult(count($files), $results);
    }

    /**
     * Collects the examples of every discovered file.
     *
     * @param array<string, string> $files
     * @return list<Example>
     *
     * @throws DoctestException when a source file cannot be read or parsed
     */
    public function collectFrom(array $files): array
    {
        $examples = [];
        foreach ($files as $absolutePath => $relativePath) {
            $examples = array_merge($examples, $this->collector->collect($absolutePath, $relativePath));
        }

        return $examples;
    }
}

<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\Doctest;

use PhpAiToolkit\Doctest\Analysis\Example;
use PhpAiToolkit\Doctest\Analysis\ProjectScanner;
use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PhpAiToolkit\Doctest\DoctestException;
use PhpAiToolkit\Doctest\Execution\ExampleRunner;
use PhpAiToolkit\Doctest\Execution\RunResult;

use function sprintf;

/**
 * Holds the examples of one configuration for a PHPUnit run.
 *
 * A PHPUnit data provider and the test it feeds are separate calls, so the
 * examples are discovered once and addressed by identifier afterwards. That is
 * also what lets a single example be re-run on its own: the identifier PHPUnit
 * prints is the identifier a --filter pattern is built from.
 */
final class DoctestExampleIndex
{
    /** @var list<Example>|null */
    private ?array $examples = null;

    /** @readonly */
    private ProjectScanner $scanner;

    /** @readonly */
    private ExampleRunner $exampleRunner;

    /**
     * Creates an index for one doctest configuration.
     */
    public function __construct(
        /** @readonly */
        private DoctestConfig $config,
        ?ProjectScanner $scanner = null,
        ?ExampleRunner $exampleRunner = null,
    ) {
        $this->scanner = $scanner ?? new ProjectScanner();
        $this->exampleRunner = $exampleRunner ?? new ExampleRunner();
    }

    /**
     * Returns the configuration this index was built for.
     */
    public function config(): DoctestConfig
    {
        return $this->config;
    }

    /**
     * Returns every example the configuration selects, discovered on first use.
     *
     * @return list<Example>
     *
     * @throws DoctestException when a configured path is missing or a source file cannot be read
     */
    public function examples(): array
    {
        if ($this->examples === null) {
            $this->examples = $this->scanner->examples($this->config);
        }

        return $this->examples;
    }

    /**
     * Returns the example with the given identifier.
     *
     * @throws DoctestException when no example carries the identifier
     */
    public function example(string $id): Example
    {
        foreach ($this->examples() as $example) {
            if ($example->id() === $id) {
                return $example;
            }
        }

        throw new DoctestException(sprintf('No documented example is identified by "%s".', $id));
    }

    /**
     * Runs the example with the given identifier.
     *
     * @throws DoctestException when the identifier is unknown or the documented file cannot be loaded
     */
    public function run(string $id): RunResult
    {
        return $this->exampleRunner->run($this->example($id), $this->config->bootstrapPath());
    }
}

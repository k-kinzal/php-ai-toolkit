<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\Doctest;

use PhpAiToolkit\Doctest\Analysis\Example;
use PhpAiToolkit\Doctest\Config\ConfigLoader;
use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PhpAiToolkit\Doctest\DoctestException;
use PhpAiToolkit\Doctest\Execution\ExampleRunner;
use PhpAiToolkit\Doctest\Execution\RunResult;
use PhpAiToolkit\Doctest\Execution\SuiteRunner;

use function sprintf;

/**
 * Holds the examples of one doctest configuration for a PHPUnit run.
 *
 * A PHPUnit data provider and the test it feeds are separate calls, so the
 * examples are discovered once and addressed by identifier afterwards. That is
 * also what lets a single failing example be re-run on its own from the
 * command line: the identifier PHPUnit prints is the identifier doctest takes.
 */
final class DoctestExampleIndex
{
    /** @var list<Example>|null */
    private ?array $examples = null;

    private ?DoctestConfig $config = null;

    /** @readonly */
    private ConfigLoader $configLoader;

    /** @readonly */
    private SuiteRunner $suiteRunner;

    /** @readonly */
    private ExampleRunner $exampleRunner;

    /**
     * Creates an index for one doctest configuration file.
     *
     * @param string $configPath path to doctest.yaml, absolute or relative to the working directory
     */
    public function __construct(
        /** @readonly */
        private string $configPath,
        ?ConfigLoader $configLoader = null,
        ?SuiteRunner $suiteRunner = null,
        ?ExampleRunner $exampleRunner = null,
    ) {
        $this->configLoader = $configLoader ?? new ConfigLoader();
        $this->suiteRunner = $suiteRunner ?? new SuiteRunner();
        $this->exampleRunner = $exampleRunner ?? new ExampleRunner();
    }

    /**
     * Returns the configuration path this index was built for.
     */
    public function configPath(): string
    {
        return $this->configPath;
    }

    /**
     * Returns the resolved configuration, loading it on first use.
     *
     * @throws DoctestException when the configuration is missing or invalid
     */
    public function config(): DoctestConfig
    {
        if ($this->config === null) {
            $this->config = $this->configLoader->load($this->configPath);
        }

        return $this->config;
    }

    /**
     * Returns every example the configuration selects, discovered on first use.
     *
     * @return list<Example>
     *
     * @throws DoctestException when the configuration or a scanned source file cannot be read
     */
    public function examples(): array
    {
        if ($this->examples === null) {
            $this->examples = $this->suiteRunner->collect($this->config());
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
        return $this->exampleRunner->run($this->example($id), $this->config()->bootstrapPath());
    }
}

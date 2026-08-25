<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Cli;

use function array_shift;

use Closure;

use function sprintf;

use Toolkit\DocGen\DocGenException;

/**
 * CLI entry point for DocGen.
 */
final class Application
{
    private const VERSION = '1.0.0';

    /** @readonly */
    private DocGenOutputWriter $writer;

    /** @readonly */
    private DocGenCliArgumentParser $argumentParser;

    /** @readonly */
    private DocGenHelpText $helpText;

    /** @readonly */
    private DocGenGenerationRunner $generationRunner;

    /**
     * Creates the DocGen CLI application for a project working directory.
     */
    public function __construct(
        /** @readonly */
        private string $workingDirectory,
        ?Closure $stdout = null,
        ?Closure $stderr = null,
        ?DocGenCliArgumentParser $argumentParser = null,
        ?DocGenHelpText $helpText = null,
        ?DocGenGenerationRunner $generationRunner = null,
    ) {
        $this->writer = new DocGenOutputWriter($stdout, $stderr);
        $this->argumentParser = $argumentParser ?? new DocGenCliArgumentParser();
        $this->helpText = $helpText ?? new DocGenHelpText();
        $this->generationRunner = $generationRunner ?? new DocGenGenerationRunner(
            $this->workingDirectory,
            null,
            null,
            $this->writer,
        );
    }

    /**
     * Runs the CLI with raw process arguments.
     *
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        array_shift($argv);
        try {
            $arguments = $this->argumentParser->parse($argv);
        } catch (DocGenException $exception) {
            $this->writer->writeError(sprintf("DocGen error: %s\n", $exception->getMessage()));

            return 2;
        }

        if ($arguments['help']) {
            $this->writer->write($this->helpText->text());

            return 0;
        }

        if ($arguments['version']) {
            $this->writer->write(sprintf("docgen %s\n", self::VERSION));

            return 0;
        }

        return $this->generationRunner->run($arguments);
    }
}

<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Cli;

use Override;

use function sprintf;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Toolkit\DocGen\DocGenException;

/**
 * The docgen console command: reads the options and generates the site.
 */
final class DocGenCommand extends Command
{
    /**
     * The name of the command, which is also the name of the executable.
     */
    public const NAME = 'docgen';

    /** @readonly */
    private string $workingDirectory;

    /** @readonly */
    private DocGenCliArgumentParser $argumentParser;

    /** @readonly */
    private DocGenHelpText $helpText;

    /**
     * Creates the command for a project working directory.
     */
    public function __construct(string $workingDirectory, ?DocGenCliArgumentParser $argumentParser = null, ?DocGenHelpText $helpText = null)
    {
        $this->workingDirectory = $workingDirectory;
        $this->argumentParser = $argumentParser ?? new DocGenCliArgumentParser();
        $this->helpText = $helpText ?? new DocGenHelpText();
        parent::__construct(self::NAME);
    }

    /**
     * Declares the options and the help of the command.
     */
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Generate a static HTML documentation site for the composer packages of the project')
            ->setDefinition($this->argumentParser->definition())
            ->setHelp($this->helpText->text());
    }

    /**
     * Runs the command, reporting a command line it cannot read.
     *
     * An unknown option, a missing value or a stray argument is reported in
     * one line with a pointer to the help, and exits 2 like any other error.
     */
    #[Override]
    public function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            return parent::run($input, $output);
        } catch (ExceptionInterface $exception) {
            $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
            $errorOutput->writeln([
                sprintf('DocGen error: %s', $exception->getMessage()),
                sprintf('Run "%s --help" to see every option.', self::NAME),
            ], OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET);

            return 2;
        }
    }

    /**
     * Generates the site the options describe.
     *
     * Messages are written raw, so a warning that quotes a generic type such
     * as list<string> is not taken for a console style tag.
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $writer = new DocGenOutputWriter(
            static function (string $message) use ($output): void {
                $output->write($message, false, OutputInterface::OUTPUT_RAW);
            },
            static function (string $message) use ($errorOutput): void {
                $errorOutput->write($message, false, OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET);
            },
        );

        try {
            $arguments = $this->argumentParser->read($input);
        } catch (DocGenException $exception) {
            $writer->writeError(sprintf("DocGen error: %s\n", $exception->getMessage()));

            return 2;
        }

        return (new DocGenGenerationRunner($this->workingDirectory, null, null, $writer))->run($arguments);
    }
}

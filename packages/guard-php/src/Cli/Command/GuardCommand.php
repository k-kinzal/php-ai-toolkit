<?php

declare(strict_types=1);

namespace Guard\Cli\Command;

use Guard\Cli\FormatDetector;
use Guard\Diagnostic\PolicyException;
use JsonException;
use Nette\Neon\Exception as NeonException;
use Override;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A guard console command: shared options, error reporting and exit codes.
 *
 * Every failure is reported in one line on standard error that names the
 * problem and how to fix it, and exits 2, so a caller can tell a broken
 * command line or policy apart from a violated rule, which exits 1.
 */
abstract class GuardCommand extends Command
{
    /**
     * The policy file a command reads when --config is not given.
     */
    public const DEFAULT_CONFIG = 'guard.yaml';

    /**
     * The report formats --format accepts.
     */
    public const FORMATS = ['text', 'human', 'ai', 'json'];

    /**
     * The exit code of an invalid command line, policy or input document.
     */
    public const INVALID = 2;

    /**
     * Creates a command that works on one project directory.
     */
    public function __construct(private string $directory, string $name)
    {
        parent::__construct($name);
    }

    /**
     * Runs the command, reporting a command line it cannot read.
     *
     * An unknown option, a missing value or a stray argument is reported in
     * one line with a pointer to the help of this command.
     */
    #[Override]
    public function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            return parent::run($input, $output);
        } catch (ExceptionInterface $exception) {
            $this->error($output, $exception->getMessage(), sprintf('Run "guard %s --help" to see its options.', $this->getName() ?? ''));

            return self::INVALID;
        }
    }

    /**
     * Suggests the values of the options every guard command shares.
     */
    #[Override]
    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestOptionValuesFor('format')) {
            $suggestions->suggestValues(self::FORMATS);
        }
    }

    /**
     * Returns the absolute path of the policy file --config names.
     *
     * @throws PolicyException when --config was given an empty value
     */
    public function configPath(InputInterface $input): string
    {
        $config = $input->getOption('config');
        if (!is_string($config) || $config === '') {
            throw new PolicyException('Provide a non-empty --config path, such as --config=guard.yaml.');
        }

        return str_starts_with($config, '/') ? $config : $this->directory . '/' . $config;
    }

    /**
     * Returns the report format --format names.
     *
     * @throws PolicyException when the format is not supported
     */
    public function format(InputInterface $input): string
    {
        $format = $input->getOption('format');
        if ($format === null) {
            return (new FormatDetector())->detect();
        }
        if (!is_string($format) || !in_array($format, self::FORMATS, true)) {
            throw new PolicyException(sprintf('Unsupported --format "%s". Use --format=text, human, ai or json.', is_string($format) ? $format : ''));
        }

        return $format;
    }

    /**
     * Writes one error to standard error, visible even under --quiet.
     */
    public function error(OutputInterface $output, string $message, string ...$hints): void
    {
        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $errorOutput->writeln(array_merge(['Guard error: ' . $message], $hints), OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET);
    }

    /**
     * Declares the --config option.
     */
    protected function addConfigOption(): void
    {
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Policy file, relative to the working directory', self::DEFAULT_CONFIG);
    }

    /**
     * Declares the --format option.
     */
    protected function addFormatOption(): void
    {
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'Report format: text (human), ai or json; defaults to ai in agent sessions');
    }

    /**
     * Runs the command, reporting an invalid policy or input document.
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            return $this->handle($input, $output);
        } catch (RuntimeException | JsonException | NeonException $exception) {
            $this->error($output, $exception->getMessage());

            return self::INVALID;
        }
    }

    /**
     * Does the work of the command.
     *
     * @throws RuntimeException when the policy or an input document is invalid
     * @throws JsonException when a JSON document is malformed
     * @throws NeonException when a NEON document is malformed
     */
    abstract protected function handle(InputInterface $input, OutputInterface $output): int;
}

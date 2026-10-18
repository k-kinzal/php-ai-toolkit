<?php

declare(strict_types=1);

namespace DecisionsLint\Cli\Command;

use DecisionsLint\Cli\FormatDetector;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\Transport;
use DecisionsLint\Reporting\ReporterFactory;
use JsonException;
use Override;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A decisions-lint console command: shared options, error reporting and exit codes.
 *
 * Every failure is reported in one line on standard error that names the
 * problem and how to fix it, and exits 2, so a caller can tell a broken
 * command line, configuration or provider apart from a violated rule, which exits 1.
 */
abstract class LintCommand extends Command
{
    /**
     * The configuration a command reads when --config is not given.
     */
    public const DEFAULT_CONFIG = 'decisions.yaml';

    /**
     * The exit code of an invalid command line, configuration, rule file or provider response.
     */
    public const INVALID = 2;

    /**
     * Creates a command that works on one project directory.
     *
     * @param ?Transport $transport the transport to reach the provider with, or null for cURL
     */
    public function __construct(private string $directory, string $name, private ?Transport $transport = null)
    {
        parent::__construct($name);
    }

    /**
     * Runs the command, reporting a command line it cannot read.
     */
    #[Override]
    public function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            return parent::run($input, $output);
        } catch (ExceptionInterface $exception) {
            $this->error($output, $exception->getMessage(), sprintf('Run "decisions-lint %s --help" to see its options.', $this->getName() ?? ''));

            return self::INVALID;
        }
    }

    /**
     * Suggests the values of the options every command shares.
     */
    #[Override]
    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestOptionValuesFor('format')) {
            $suggestions->suggestValues(ReporterFactory::FORMATS);
        }
    }

    /**
     * Returns the project directory the command works on.
     */
    public function directory(): string
    {
        return $this->directory;
    }

    /**
     * Returns the injected transport, or null when the provider is reached over cURL.
     */
    public function transport(): ?Transport
    {
        return $this->transport;
    }

    /**
     * Returns the absolute path of the configuration --config names.
     *
     * @throws LintException when --config was given an empty value
     */
    public function configPath(InputInterface $input): string
    {
        $config = $input->getOption('config');
        if (!is_string($config) || $config === '') {
            throw new LintException('Provide a non-empty --config path, such as --config=decisions.yaml.');
        }

        return str_starts_with($config, '/') ? $config : $this->directory . '/' . $config;
    }

    /**
     * Returns the output format --format names, or the one the session suggests.
     *
     * @param list<string> $allowed the formats this command supports
     * @throws LintException when the format is not supported
     */
    public function format(InputInterface $input, array $allowed): string
    {
        $format = $input->getOption('format');
        if ($format === null) {
            $detected = (new FormatDetector())->detect();

            return in_array($detected, $allowed, true) ? $detected : 'text';
        }
        if (!is_string($format) || !in_array($format, $allowed, true)) {
            throw new LintException(sprintf('Unsupported --format "%s". Use --format=%s.', is_string($format) ? $format : '', implode(', ', $allowed)));
        }

        return $format;
    }

    /**
     * Returns the rule ids --rule names.
     *
     * @return list<string>
     * @throws LintException when --rule was given an empty value
     */
    public function ruleIds(InputInterface $input): array
    {
        $ids = [];
        foreach (is_array($input->getOption('rule')) ? $input->getOption('rule') : [] as $id) {
            if (!is_string($id) || trim($id) === '') {
                throw new LintException('Provide a rule id for --rule, such as --rule=cohesion.method-belongs-to-class.');
            }
            $ids[] = trim($id);
        }

        return $ids;
    }

    /**
     * Returns the paths given as arguments, relative to the configuration directory.
     *
     * @return list<string>
     */
    public function paths(InputInterface $input): array
    {
        $paths = [];
        foreach (is_array($input->getArgument('paths')) ? $input->getArgument('paths') : [] as $path) {
            if (is_string($path) && trim($path) !== '') {
                $paths[] = trim($path);
            }
        }

        return $paths;
    }

    /**
     * Writes one error to standard error, visible even under --quiet.
     */
    public function error(OutputInterface $output, string $message, string ...$hints): void
    {
        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $errorOutput->writeln(array_merge(['Decisions error: ' . $message], $hints), OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET);
    }

    /**
     * Declares the --config option.
     */
    protected function addConfigOption(): void
    {
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file, relative to the working directory', self::DEFAULT_CONFIG);
    }

    /**
     * Declares the --format option with the formats a command supports.
     *
     * @param list<string> $formats
     */
    protected function addFormatOption(array $formats): void
    {
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: ' . implode(', ', $formats) . '; defaults to ai in agent sessions');
    }

    /**
     * Declares the repeatable --rule option.
     */
    protected function addRuleOption(): void
    {
        $this->addOption('rule', 'r', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Run only this rule id; repeat for several');
    }

    /**
     * Declares the optional paths argument.
     */
    protected function addPathsArgument(): void
    {
        $this->addArgument('paths', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Files, directories or globs relative to the configuration directory; defaults to the configured paths');
    }

    /**
     * Runs the command, reporting an invalid configuration, rule file or provider response.
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            return $this->handle($input, $output);
        } catch (RuntimeException | JsonException $exception) {
            $this->error($output, $exception->getMessage());

            return self::INVALID;
        }
    }

    /**
     * Does the work of the command.
     *
     * @throws RuntimeException when the configuration, a rule file, an input or a provider response is invalid
     * @throws JsonException when a report cannot be encoded
     */
    abstract protected function handle(InputInterface $input, OutputInterface $output): int;
}

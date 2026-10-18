<?php

declare(strict_types=1);

namespace DecisionsLint\Cli\Command;

use DecisionsLint\Cli\CheckRun;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Http\Transport;
use DecisionsLint\Reporting\ReporterFactory;
use JsonException;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lints the configured files against the rules and reports the violations.
 */
final class CheckCommand extends LintCommand
{
    /**
     * Creates the check command for a project directory.
     */
    public function __construct(string $directory, ?Transport $transport = null)
    {
        parent::__construct($directory, 'check', $transport);
    }

    /**
     * Declares the description, options and arguments.
     */
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Judge the configured files against the rules with the decision model and report violations');
        $this->addConfigOption();
        $this->addFormatOption(ReporterFactory::FORMATS);
        $this->addRuleOption();
        $this->addOption('fail-on', null, InputOption::VALUE_REQUIRED, 'Weakest severity that exits 1: error, warning, info or never; defaults to fail_on in the configuration');
        $this->addPathsArgument();
    }

    /**
     * Runs the check.
     *
     * @throws LintException when --fail-on is not a severity
     * @throws JsonException when the report cannot be encoded
     */
    #[Override]
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $failOn = $input->getOption('fail-on');
        if ($failOn !== null && (!is_string($failOn) || trim($failOn) === '')) {
            throw new LintException('Provide a severity for --fail-on, such as --fail-on=warning.');
        }

        return (new CheckRun($this->transport()))->run($this->configPath($input), $this->format($input, ReporterFactory::FORMATS), $this->paths($input), $this->ruleIds($input), is_string($failOn) ? trim($failOn) : null, $output);
    }
}

<?php

declare(strict_types=1);

namespace DecisionsLint\Cli\Command;

use DecisionsLint\Config\ConfigurationLoader;
use DecisionsLint\Lint\Planner;
use DecisionsLint\Reporting\PlanReporter;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Shows which declarations the rules would judge and how many requests that takes, without contacting the provider.
 */
final class PlanCommand extends LintCommand
{
    /**
     * Creates the plan command for a project directory.
     */
    public function __construct(string $directory)
    {
        parent::__construct($directory, 'plan');
    }

    /**
     * Declares the description, options and arguments.
     */
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('List the declarations each rule would judge and the requests that takes, without calling the provider');
        $this->addConfigOption();
        $this->addFormatOption(['text', 'json']);
        $this->addRuleOption();
        $this->addPathsArgument();
    }

    /**
     * Plans the run and prints it.
     */
    #[Override]
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $configuration = (new ConfigurationLoader())->load($this->configPath($input));
        $plan = (new Planner())->plan($configuration, $this->paths($input), $this->ruleIds($input));
        $output->write((new PlanReporter())->render($plan, $this->format($input, ['text', 'json'])), false, OutputInterface::OUTPUT_RAW);

        return 0;
    }
}

<?php

declare(strict_types=1);

namespace DecisionsLint\Cli\Command;

use DecisionsLint\Config\ConfigurationLoader;
use DecisionsLint\Reporting\RuleCatalogReporter;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists the effective rules after imports and overrides.
 */
final class RulesCommand extends LintCommand
{
    /**
     * Creates the rules command for a project directory.
     */
    public function __construct(string $directory)
    {
        parent::__construct($directory, 'rules');
    }

    /**
     * Declares the description and options.
     */
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('List the effective rules after imports and overrides, with their severity, target and decision');
        $this->addConfigOption();
        $this->addFormatOption(['text', 'json']);
    }

    /**
     * Loads the configuration and prints its rules.
     */
    #[Override]
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $configuration = (new ConfigurationLoader())->load($this->configPath($input));
        $output->write((new RuleCatalogReporter())->render($configuration->rules, $this->format($input, ['text', 'json'])), false, OutputInterface::OUTPUT_RAW);

        return 0;
    }
}

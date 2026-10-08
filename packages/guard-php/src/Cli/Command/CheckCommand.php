<?php

declare(strict_types=1);

namespace Guard\Cli\Command;

use Guard\Cli\CheckRun;
use Guard\Extension\Registry;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * guard check: reports policy violations without changing any file.
 */
final class CheckCommand extends GuardCommand
{
    /**
     * Creates the command for a project directory.
     */
    public function __construct(string $directory, private ?Registry $registry = null)
    {
        parent::__construct($directory, 'check');
    }

    /**
     * Declares the options and the help of the command.
     */
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Check the project against its policy without changing any file');
        $this->addConfigOption();
        $this->addFormatOption();
        $this->addOption('query', null, InputOption::VALUE_REQUIRED, 'Search rule IDs, paths and messages; display only');
        $this->addOption('fixable', null, InputOption::VALUE_NONE, 'Show only automatically fixable violations; display only');
        $this->addOption('level', null, InputOption::VALUE_REQUIRED, 'Show only error or warning diagnostics; display only');
        $this->addOption('baseline', null, InputOption::VALUE_REQUIRED, 'Baseline path relative to the policy file; defaults to guard-baseline.json if present');
        $this->addOption('no-baseline', null, InputOption::VALUE_NONE, 'Report all violations without applying a baseline');

    }

    /**
     * Reports the violations of the policy.
     */
    #[Override]
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        return (new CheckRun($this->registry))->run($this->configPath($input), $this->format($input), $input, $output);
    }
}

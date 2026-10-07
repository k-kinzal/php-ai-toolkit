<?php

declare(strict_types=1);

namespace Guard\Cli\Command;

use Guard\Cli\PolicyRun;
use Guard\Extension\Registry;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * guard apply: repairs what the policy can repair and reports what remains.
 */
final class ApplyCommand extends GuardCommand
{
    /**
     * Creates the command for a project directory.
     */
    public function __construct(string $directory, private ?Registry $registry = null)
    {
        parent::__construct($directory, 'apply');
    }

    /**
     * Declares the options and the help of the command.
     */
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Repair the violations guard can fix, then report what remains');
        $this->addConfigOption();
        $this->addFormatOption();
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'List the files that would change without writing them');
        $this->setHelp(<<<'TEXT'
            Writes the repairs the policy defines, then reports the violations that
            remain. Repairs are written all together or not at all: while a required
            violation cannot be repaired, nothing is written and the report says
            "blocked". Use --dry-run to see the changes first.

            Exit codes:
              0  passed, or only warnings remain
              1  a required rule is still violated
              2  invalid command line, policy or input document
            TEXT);
    }

    /**
     * Repairs the project and reports the violations that remain.
     */
    #[Override]
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        return (new PolicyRun($this->registry))->run($this->configPath($input), $this->format($input), true, $input->getOption('dry-run') === true, $output);
    }
}

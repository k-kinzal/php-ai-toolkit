<?php

declare(strict_types=1);

namespace Guard\Cli\Command;

use Guard\Cli\PolicyRun;
use Guard\Extension\Registry;
use Override;
use Symfony\Component\Console\Input\InputInterface;
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
        $this->setHelp(<<<'TEXT'
            Checks every policy in the policy file and reports each violated required
            rule as an error and each recommendation as a warning. No file is changed;
            run "guard apply" to repair what can be repaired.

            Running guard without a command runs check.

            Exit codes:
              0  passed, or only warnings
              1  a required rule is violated
              2  invalid command line, policy or input document
            TEXT);
    }

    /**
     * Reports the violations of the policy.
     */
    #[Override]
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        return (new PolicyRun($this->registry))->run($this->configPath($input), $this->format($input), false, false, $output);
    }
}

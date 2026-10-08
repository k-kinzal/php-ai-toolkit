<?php

declare(strict_types=1);

namespace Guard\Cli\Command;

use Guard\Config\RuleCatalog;
use Guard\Diagnostic\PolicyException;
use Guard\Reporting\RuleReporter;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists active rules, their messages, and repair capabilities.
 */
final class RulesCommand extends GuardCommand
{
    /**
     * Creates the rule browser for a project directory.
     */
    public function __construct(string $directory)
    {
        parent::__construct($directory, 'rules');
    }

    /**
     * Declares the short command description and search options.
     */
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('List active rules, their messages and automatic fix support');
        $this->addConfigOption();
        $this->addFormatOption();
        $this->addOption('query', null, InputOption::VALUE_REQUIRED, 'Case-insensitive search in rule IDs, targets, levels, messages and fixability');
    }

    /**
     * Reads the resolved policy without reading or changing target files.
     */
    #[Override]
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $format = $this->format($input);
        $query = $input->getOption('query');
        if ($query !== null && (!is_string($query) || trim($query) === '')) {
            throw new PolicyException('Provide non-empty search text for --query, such as --query=phpstan.');
        }
        $reporter = new RuleReporter();
        $rules = $reporter->filter((new RuleCatalog())->load($this->configPath($input)), $query ?? '');
        $output->write($reporter->render($rules, $format, $output->isDecorated()), false, OutputInterface::OUTPUT_RAW);
        return 0;
    }
}

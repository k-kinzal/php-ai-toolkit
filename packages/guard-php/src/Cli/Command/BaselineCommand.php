<?php

declare(strict_types=1);

namespace Guard\Cli\Command;

use Guard\Cli\BaselineFile;
use Guard\Config\ConfigurationLoader;
use Guard\Execution\Pipeline;
use Guard\Execution\Registry;
use Guard\Reporting\Baseline;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Records all current diagnostics without applying repairs or consuming an old baseline.
 */
final class BaselineCommand extends GuardCommand
{
    /**
     * Creates the baseline generator for this project.
     */
    public function __construct(string $directory, private ?Registry $registry = null)
    {
        parent::__construct($directory, 'baseline');
    }

    /**
     * Declares output options shared by interactive and automated callers.
     */
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Record current violations in a baseline for subsequent checks');
        $this->addConfigOption();
        $this->addFormatOption();
        $this->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Baseline path relative to the policy file', BaselineFile::NAME);
    }

    /**
     * Evaluates everything before writing; a successful generation exits zero even with violations.
     */
    #[Override]
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->configPath($input);
        $format = $this->format($input);
        $file = new BaselineFile();
        $requested = $input->getOption('output');
        $target = $file->path($path, is_string($requested) ? $requested : null);
        $file->validateTarget($target);
        $configuration = (new ConfigurationLoader())->load($path);
        $plan = (new Pipeline($this->registry))->run($configuration, $path, false);
        $file->write($target, (new Baseline())->capture($plan->findings));
        $count = count($plan->findings);
        $message = $format === 'json'
            ? json_encode(['baseline' => $target, 'count' => $count, 'success' => true], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : sprintf('Recorded %d diagnostics in %s. Review and commit the baseline; guard check --no-baseline shows all violations.', $count, $target);
        $output->writeln($message, OutputInterface::OUTPUT_RAW);
        return 0;
    }
}

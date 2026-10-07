<?php

declare(strict_types=1);

namespace Guard\Cli\Command;

use Guard\Init\Initializer;
use Guard\Init\PresetCatalog;
use Guard\Policy\PolicyException;
use Override;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * guard init: writes a first policy file for the project.
 */
final class InitCommand extends GuardCommand
{
    /**
     * Creates the command for a project directory.
     */
    public function __construct(string $directory)
    {
        parent::__construct($directory, 'init');
    }

    /**
     * Suggests the shipped preset names for --import.
     */
    #[Override]
    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestOptionValuesFor('import')) {
            $suggestions->suggestValues((new PresetCatalog())->names());
        }
    }

    /**
     * Returns the preset names every --import occurrence names, or null when none does.
     *
     * @return ?list<string>
     * @throws PolicyException when an occurrence names no preset
     */
    public function imports(InputInterface $input): ?array
    {
        $values = $input->getOption('import');
        if (!is_array($values) || $values === []) {
            return null;
        }
        $imports = [];
        foreach ($values as $value) {
            foreach (explode(',', is_string($value) ? $value : '') as $name) {
                if ($name === '') {
                    throw new PolicyException('Provide one or more preset names for --import, separated by commas.');
                }
                $imports[] = $name;
            }
        }

        return $imports;
    }

    /**
     * Declares the options and the help of the command.
     */
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Create a policy file with the presets that fit the project');
        $this->addConfigOption();
        $this->addOption('import', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Import only these presets, comma-separated or repeated, instead of detecting them');
        $this->setHelp(sprintf(<<<'TEXT'
            Writes a policy file for the project and refuses to overwrite one that
            exists. Without --import, the presets are selected from the tools and
            documents the project has; --import names them instead.

            Presets: %s

            Exit codes:
              0  policy file created
              2  invalid command line, existing policy file or unknown preset
            TEXT, implode(', ', (new PresetCatalog())->names())));
    }

    /**
     * Writes the policy file.
     */
    #[Override]
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->configPath($input);
        (new Initializer())->write($path, $this->imports($input));
        $output->writeln('Created ' . $path . '. Review the detected recommendations, then run guard check.', OutputInterface::OUTPUT_RAW);

        return 0;
    }
}

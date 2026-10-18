<?php

declare(strict_types=1);

namespace DecisionsLint\Cli\Command;

use DecisionsLint\Config\ConfigurationLoader;
use DecisionsLint\Config\DocumentLoader;
use DecisionsLint\Config\RuleReader;
use DecisionsLint\Config\SchemaCatalog;
use DecisionsLint\Diagnostic\LintException;
use Override;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Checks decisions.yaml and rule files against their schemas and reports what each defines.
 */
final class ValidateCommand extends LintCommand
{
    /**
     * Creates the validate command for a project directory.
     */
    public function __construct(string $directory)
    {
        parent::__construct($directory, 'validate');
    }

    /**
     * Declares the description, options and arguments.
     */
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Check the configuration and its imports, and any rule file given, against their schemas');
        $this->addConfigOption();
        $this->addArgument('files', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Rule files to validate on their own, relative to the working directory');
    }

    /**
     * Validates the configuration, then each rule file, and prints one line per document.
     *
     * @throws LintException when a document is missing, malformed or departs from its schema
     */
    #[Override]
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $configPath = $this->configPath($input);
        $configuration = (new ConfigurationLoader())->load($configPath);
        $enabled = count($configuration->activeRules());
        $output->writeln(sprintf('%s is valid: %d %s, %d enabled, provider %s (%s).', basename($configPath), count($configuration->rules), count($configuration->rules) === 1 ? 'rule' : 'rules', $enabled, $configuration->provider->name, $configuration->provider->model), OutputInterface::OUTPUT_RAW);
        $loader = new DocumentLoader();
        $reader = new RuleReader();
        foreach ($this->files($input) as $file) {
            $path = str_starts_with($file, '/') ? $file : $this->directory() . '/' . $file;
            $document = $loader->load($path, SchemaCatalog::RULES, $file);
            $rules = is_array($document['rules'] ?? null) ? $document['rules'] : [];
            foreach ($rules as $id => $definition) {
                $reader->read((string) $id, is_array($definition) ? ['source' => [$file]] + $this->mapping($definition) : []);
            }
            $output->writeln(sprintf('%s is valid: %d %s.', $file, count($rules), count($rules) === 1 ? 'rule' : 'rules'), OutputInterface::OUTPUT_RAW);
        }

        return 0;
    }

    /**
     * Returns the rule files given as arguments.
     *
     * @return list<string>
     */
    public function files(InputInterface $input): array
    {
        $files = [];
        foreach (is_array($input->getArgument('files')) ? $input->getArgument('files') : [] as $file) {
            if (is_string($file) && trim($file) !== '') {
                $files[] = trim($file);
            }
        }

        return $files;
    }

    /**
     * Reads an array as a mapping with string keys.
     *
     * @param array<array-key, mixed> $value
     * @return array<string, mixed>
     */
    public function mapping(array $value): array
    {
        $mapping = [];
        foreach ($value as $key => $entry) {
            $mapping[(string) $key] = $entry;
        }

        return $mapping;
    }
}

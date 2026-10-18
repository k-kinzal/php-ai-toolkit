<?php

declare(strict_types=1);

namespace DecisionsLint\Cli\Command;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\ProviderSettings;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Writes a starting decisions.yaml that imports the shipped rule files.
 */
final class InitCommand extends LintCommand
{
    /**
     * Creates the init command for a project directory.
     */
    public function __construct(string $directory, private ?string $rulesDirectory = null)
    {
        parent::__construct($directory, 'init');
    }

    /**
     * Declares the description and options.
     */
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Create a decisions.yaml that imports the shipped rule files');
        $this->addConfigOption();
        $this->addOption('provider', null, InputOption::VALUE_REQUIRED, 'Decision API: ' . implode(', ', ProviderSettings::NAMES), ProviderSettings::JEV);
        $this->addOption('path', 'p', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Directory to lint; repeat for several', ['src']);
    }

    /**
     * Writes the configuration, refusing to overwrite one.
     *
     * @throws LintException when the file exists, the provider is unknown, or the file cannot be written
     */
    #[Override]
    protected function handle(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->configPath($input);
        if (file_exists($path)) {
            throw new LintException(sprintf('%s already exists. Edit it, or pass --config to write another file.', $path));
        }
        $provider = $input->getOption('provider');
        if (!is_string($provider) || !in_array($provider, ProviderSettings::NAMES, true)) {
            throw new LintException(sprintf('Unsupported --provider "%s". Use --provider=%s.', is_string($provider) ? $provider : '', implode(', ', ProviderSettings::NAMES)));
        }
        $paths = [];
        foreach (is_array($input->getOption('path')) ? $input->getOption('path') : [] as $entry) {
            if (is_string($entry) && trim($entry) !== '') {
                $paths[] = trim($entry);
            }
        }
        $content = $this->document(ProviderSettings::defaults($provider), $paths === [] ? ['src'] : $paths, $this->imports(dirname($path)));
        if (file_put_contents($path, $content) === false) {
            throw new LintException(sprintf('Could not write %s. Check the directory permissions.', $path));
        }
        $settings = ProviderSettings::defaults($provider);
        $key = $settings->requiresApiKey() ? sprintf('Export %s, ', $settings->apiKeyEnv) : sprintf('Make sure a %s server answers at %s, ', $provider, $settings->baseUrl);
        $output->writeln(sprintf('Wrote %s. %sreview the imported rules with decisions-lint rules, then run decisions-lint check.', $path, $key), OutputInterface::OUTPUT_RAW);

        return 0;
    }

    /**
     * Returns the YAML of a starting configuration.
     *
     * @param list<string> $paths
     * @param list<string> $imports
     */
    public function document(ProviderSettings $provider, array $paths, array $imports): string
    {
        $lines = [
            '# yaml-language-server: $schema=' . $this->schemaPath($imports),
            '# Nuanced rules judged by a decision model. Imported rules can be overridden below by id.',
            'version: 1',
            'provider:',
            '  name: ' . $provider->name,
            '  model: ' . $provider->model,
            '  api_key_env: ' . $provider->apiKeyEnv,
            'paths:',
        ];
        foreach ($paths as $entry) {
            $lines[] = '  - ' . $entry;
        }
        $lines[] = 'exclude: []';
        $lines[] = 'fail_on: error';
        $lines[] = $imports === [] ? 'imports: []' : 'imports:';
        foreach ($imports as $import) {
            $lines[] = '  - ' . $import;
        }
        $lines[] = 'rules: {}';

        return implode("\n", $lines) . "\n";
    }

    /**
     * Returns the shipped rule files as paths relative to the configuration directory.
     *
     * @return list<string>
     */
    public function imports(string $directory): array
    {
        $rules = $this->rulesDirectory ?? dirname(__DIR__, 3) . '/rules';
        $vendor = $directory . '/vendor/k-kinzal/decisions-lint/rules';
        $prefix = is_dir($vendor) ? 'vendor/k-kinzal/decisions-lint/rules' : $this->relative($directory, $rules);
        $files = glob($rules . '/*.yaml');
        $imports = [];
        foreach ($files === false ? [] : $files as $file) {
            $imports[] = $prefix . '/' . basename($file);
        }
        sort($imports);

        return $imports;
    }

    /**
     * Returns the schema path the editor hint points to, next to the imported rules.
     *
     * @param list<string> $imports
     */
    public function schemaPath(array $imports): string
    {
        return $imports === [] ? 'https://k-kinzal.github.io/php-ai-toolkit/decisions-lint/decisions.schema.json' : dirname(dirname($imports[0])) . '/schema/decisions.schema.json';
    }

    /**
     * Returns a path relative to a directory, using .. where needed.
     */
    public function relative(string $from, string $to): string
    {
        $fromReal = realpath($from);
        $toReal = realpath($to);
        $fromParts = explode('/', trim($fromReal === false ? $from : $fromReal, '/'));
        $toParts = explode('/', trim($toReal === false ? $to : $toReal, '/'));
        while ($fromParts !== [] && $toParts !== [] && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        return implode('/', array_merge(array_fill(0, count($fromParts), '..'), $toParts));
    }
}

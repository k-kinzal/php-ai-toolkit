<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Config;

use function dirname;
use function is_array;
use function is_file;

use PhpAiToolkit\TreeGuard\TreeGuardException;

use function sprintf;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads and validates tree.yaml.
 */
final class ConfigLoader
{
    /** @readonly */
    private ConfigStringListReader $stringListReader;

    /** @readonly */
    private RuleListConfigReader $ruleListConfigReader;

    /** @readonly */
    private ReportConfigReader $reportConfigReader;

    /**
     * Creates a config loader from YAML section readers.
     */
    public function __construct(
        ?ConfigStringListReader $stringListReader = null,
        ?RuleListConfigReader $ruleListConfigReader = null,
        ?ReportConfigReader $reportConfigReader = null,
    ) {
        $this->stringListReader = $stringListReader ?? new ConfigStringListReader();
        $this->ruleListConfigReader = $ruleListConfigReader ?? new RuleListConfigReader();
        $this->reportConfigReader = $reportConfigReader ?? new ReportConfigReader();
    }

    /**
     * Loads and validates a TreeGuard YAML configuration file.
     *
     * @throws TreeGuardException when the file is missing, unparsable, or not a mapping
     */
    public function load(string $path): TreeGuardConfig
    {
        if (!is_file($path)) {
            throw new TreeGuardException(sprintf('TreeGuard config not found: %s', $path));
        }

        try {
            $data = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new TreeGuardException('Invalid tree.yaml: ' . $exception->getMessage(), 0, $exception);
        }

        if (!is_array($data)) {
            throw new TreeGuardException('Invalid tree.yaml: top-level value must be a mapping.');
        }

        return new TreeGuardConfig(
            dirname($path),
            $this->stringListReader->read($data, 'paths', ['src'], ''),
            $this->stringListReader->read($data, 'exclude', [], ''),
            $this->ruleListConfigReader->read($data['rules'] ?? []),
            $this->reportConfigReader->read($data['report'] ?? []),
        );
    }
}

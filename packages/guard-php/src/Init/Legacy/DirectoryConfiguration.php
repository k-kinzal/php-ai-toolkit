<?php

declare(strict_types=1);

namespace Guard\Init\Legacy;

use function dirname;

use Guard\Config\Reader\DirectoryRuleListConfigReader;
use Guard\Config\Validation\DirectoryConfigStringListReader;
use Guard\Config\Value\StructureConfig;
use Guard\Policy\PolicyException;

use function is_array;
use function is_file;
use function sprintf;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads and validates tree.yaml.
 */
final class DirectoryConfiguration
{
    /** @readonly */
    private DirectoryConfigStringListReader $stringListReader;

    /** @readonly */
    private DirectoryRuleListConfigReader $ruleListConfigReader;

    /** @readonly */
    private DirectoryReportReader $reportConfigReader;

    /**
     * Creates a config loader from YAML section readers.
     */
    public function __construct(
        ?DirectoryConfigStringListReader $stringListReader = null,
        ?DirectoryRuleListConfigReader $ruleListConfigReader = null,
        ?DirectoryReportReader $reportConfigReader = null,
    ) {
        $this->stringListReader = $stringListReader ?? new DirectoryConfigStringListReader();
        $this->ruleListConfigReader = $ruleListConfigReader ?? new DirectoryRuleListConfigReader();
        $this->reportConfigReader = $reportConfigReader ?? new DirectoryReportReader();
    }

    /**
     * Loads and validates a directory policy YAML configuration file.
     *
     * @throws PolicyException when the file is missing, unparsable, or not a mapping
     */
    public function load(string $path): StructureConfig
    {
        if (!is_file($path)) {
            throw new PolicyException(sprintf('TreeGuard config not found: %s', $path));
        }

        try {
            $data = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new PolicyException('Invalid tree.yaml: ' . $exception->getMessage(), 0, $exception);
        }

        if (!is_array($data)) {
            throw new PolicyException('Invalid tree.yaml: top-level value must be a mapping.');
        }

        $config = new StructureConfig(
            dirname($path),
            $this->stringListReader->read($data, 'paths', ['src'], ''),
            $this->stringListReader->read($data, 'exclude', [], ''),
            $this->ruleListConfigReader->read($data['rules'] ?? []),
        );
        $this->reportConfigReader->read($data['report'] ?? []);
        return $config;
    }
}

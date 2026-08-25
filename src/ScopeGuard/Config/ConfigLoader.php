<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Config;

use function dirname;
use function is_array;
use function is_file;
use function sprintf;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Toolkit\ScopeGuard\ScopeGuardException;

/**
 * Loads and validates scope.yaml.
 */
final class ConfigLoader
{
    /** @readonly */
    private ConfigStringListReader $stringListReader;

    /** @readonly */
    private ReportConfigReader $reportConfigReader;

    /**
     * Creates a config loader from YAML section readers.
     */
    public function __construct(
        ?ConfigStringListReader $stringListReader = null,
        ?ReportConfigReader $reportConfigReader = null,
    ) {
        $this->stringListReader = $stringListReader ?? new ConfigStringListReader();
        $this->reportConfigReader = $reportConfigReader ?? new ReportConfigReader();
    }

    /**
     * Loads and validates a ScopeGuard YAML configuration file.
     *
     * @throws ScopeGuardException when the file is missing, unparsable, or not a mapping
     */
    public function load(string $path): ScopeGuardConfig
    {
        if (!is_file($path)) {
            throw new ScopeGuardException(sprintf('ScopeGuard config not found: %s', $path));
        }

        try {
            $data = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new ScopeGuardException('Invalid scope.yaml: ' . $exception->getMessage(), 0, $exception);
        }

        if (!is_array($data)) {
            throw new ScopeGuardException('Invalid scope.yaml: top-level value must be a mapping.');
        }

        return new ScopeGuardConfig(
            dirname($path),
            $this->stringListReader->read($data, 'paths', ['src'], ''),
            $this->stringListReader->read($data, 'exclude', [], ''),
            $this->stringListReader->read($data, 'exempt_namespaces', [], ''),
            $this->reportConfigReader->read($data['report'] ?? []),
        );
    }
}

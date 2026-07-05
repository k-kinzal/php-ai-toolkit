<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Config;

use function dirname;
use function is_array;
use function is_file;

use PhpAiToolkit\LocGuard\LocGuardException;

use function sprintf;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads and validates loc.yaml.
 */
final class ConfigLoader
{
    /**
     * Creates a config loader from YAML section readers.
     */
    public function __construct(
        private readonly ConfigStringListReader $stringListReader = new ConfigStringListReader(),
        private readonly LimitConfigReader $limitConfigReader = new LimitConfigReader(),
        private readonly ReportConfigReader $reportConfigReader = new ReportConfigReader(),
    ) {
    }

    /**
     * Loads and validates a LocGuard YAML configuration file.
     */
    public function load(string $path): LocGuardConfig
    {
        if (!is_file($path)) {
            throw new LocGuardException(sprintf('LocGuard config not found: %s', $path));
        }

        try {
            $data = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new LocGuardException('Invalid loc.yaml: ' . $exception->getMessage(), 0, $exception);
        }

        if (!is_array($data)) {
            throw new LocGuardException('Invalid loc.yaml: top-level value must be a mapping.');
        }

        return new LocGuardConfig(
            dirname($path),
            $this->stringListReader->read($data, 'paths', ['src']),
            $this->stringListReader->read($data, 'exclude', []),
            $this->limitConfigReader->read($data['limits'] ?? []),
            $this->reportConfigReader->read($data['report'] ?? []),
        );
    }
}

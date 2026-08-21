<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Config;

use function dirname;
use function is_array;
use function is_file;

use PhpAiToolkit\Doctest\DoctestException;

use function sprintf;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads and validates doctest.yaml.
 */
final class ConfigLoader
{
    /** @readonly */
    private ConfigScalarReader $scalarReader;

    /** @readonly */
    private ConfigStringListReader $stringListReader;

    /** @readonly */
    private ReportConfigReader $reportConfigReader;

    /**
     * Creates a config loader from YAML section readers.
     */
    public function __construct(
        ?ConfigScalarReader $scalarReader = null,
        ?ConfigStringListReader $stringListReader = null,
        ?ReportConfigReader $reportConfigReader = null,
    ) {
        $this->scalarReader = $scalarReader ?? new ConfigScalarReader();
        $this->stringListReader = $stringListReader ?? new ConfigStringListReader();
        $this->reportConfigReader = $reportConfigReader ?? new ReportConfigReader();
    }

    /**
     * Loads and validates a doctest YAML configuration file.
     *
     * @throws DoctestException when the file is missing, unparsable, or not a mapping
     */
    public function load(string $path): DoctestConfig
    {
        if (!is_file($path)) {
            throw new DoctestException(sprintf('Doctest config not found: %s', $path));
        }

        try {
            $data = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new DoctestException('Invalid doctest.yaml: ' . $exception->getMessage(), 0, $exception);
        }

        if (!is_array($data)) {
            throw new DoctestException('Invalid doctest.yaml: top-level value must be a mapping.');
        }

        return new DoctestConfig(
            dirname($path),
            $this->stringListReader->read($data, 'paths', ['src'], ''),
            $this->stringListReader->read($data, 'exclude', [], ''),
            $this->scalarReader->optionalString($data, 'bootstrap', ''),
            $this->reportConfigReader->read($data['report'] ?? []),
        );
    }
}

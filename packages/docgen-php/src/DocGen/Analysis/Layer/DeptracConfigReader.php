<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Layer;

use function array_filter;
use function array_values;
use function is_array;
use function is_file;
use function is_string;
use function sprintf;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Toolkit\DocGen\DocGenException;

/**
 * Reads layer definitions and the ruleset from a deptrac.yaml file.
 *
 * Only the parts DocGen visualizes are read; unknown keys and collector
 * types are ignored so any deptrac version can be consumed.
 *
 * @visibility Toolkit\DocGen\Analysis
 */
final class DeptracConfigReader
{
    /**
     * Reads one deptrac configuration file.
     *
     * @throws DocGenException when the file is missing or unparsable
     */
    public function read(string $path): LayerModel
    {
        if (!is_file($path)) {
            throw new DocGenException(sprintf('Deptrac config not found: %s', $path));
        }

        try {
            $data = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new DocGenException('Invalid deptrac.yaml: ' . $exception->getMessage(), 0, $exception);
        }

        if (!is_array($data)) {
            throw new DocGenException('Invalid deptrac.yaml: top-level value must be a mapping.');
        }

        $sections = array_filter($data, 'is_array');
        $section = array_filter($sections['deptrac'] ?? $data, 'is_array');

        $layers = [];
        foreach (array_filter($section['layers'] ?? [], 'is_array') as $layer) {
            if (!is_string($layer['name'] ?? null)) {
                continue;
            }

            $collectors = [];
            foreach (is_array($layer['collectors'] ?? null) ? $layer['collectors'] : [] as $collector) {
                if (is_array($collector) && is_string($collector['type'] ?? null) && is_string($collector['value'] ?? null)) {
                    $collectors[] = new LayerCollector($collector['type'], $collector['value']);
                }
            }

            $layers[] = new LayerDefinition($layer['name'], $collectors);
        }

        $ruleset = [];
        foreach ($section['ruleset'] ?? [] as $layer => $allowed) {
            $ruleset[(string) $layer] = array_values(array_filter(is_array($allowed) ? $allowed : [], 'is_string'));
        }

        return new LayerModel($layers, $ruleset);
    }
}

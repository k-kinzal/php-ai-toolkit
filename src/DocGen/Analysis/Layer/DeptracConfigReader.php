<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Layer;

use function is_array;
use function is_file;
use function is_string;

use PhpAiToolkit\DocGen\DocGenException;

use function sprintf;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads layer definitions and the ruleset from a deptrac.yaml file.
 *
 * Only the parts DocGen visualizes are read; unknown keys and collector
 * types are ignored so any deptrac version can be consumed.
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

        $section = isset($data['deptrac']) && is_array($data['deptrac']) ? $data['deptrac'] : $data;

        return new LayerModel($this->layers($section), $this->ruleset($section));
    }

    /**
     * Reads the layer definitions of a deptrac section.
     *
     * @param array<array-key, mixed> $section
     *
     * @return list<LayerDefinition>
     */
    public function layers(array $section): array
    {
        $layers = [];
        foreach (is_array($section['layers'] ?? null) ? $section['layers'] : [] as $layer) {
            if (!is_array($layer) || !is_string($layer['name'] ?? null)) {
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

        return $layers;
    }

    /**
     * Reads the allowed dependency ruleset of a deptrac section.
     *
     * @param array<array-key, mixed> $section
     *
     * @return array<string, list<string>>
     */
    public function ruleset(array $section): array
    {
        $ruleset = [];
        foreach (is_array($section['ruleset'] ?? null) ? $section['ruleset'] : [] as $layer => $allowed) {
            $names = [];
            foreach (is_array($allowed) ? $allowed : [] as $name) {
                if (is_string($name)) {
                    $names[] = $name;
                }
            }

            $ruleset[(string) $layer] = $names;
        }

        return $ruleset;
    }
}

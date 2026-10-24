<?php

declare(strict_types=1);

namespace Guard\Policy\TestDependency;

use Guard\Diagnostic\PolicyException;
use stdClass;

/**
 * Builds disjoint test collectors and retains the project's production layer definitions.
 */
final class DeptracLayers
{
    /**
     * @param array<string, string> $active
     * @param list<string> $sources
     * @return list<stdClass>
     * @throws PolicyException when a layer is invalid or a custom production layer is missing
     */
    public function build(mixed $value, array $active, array $sources): array
    {
        $layers = [];
        $seen = [];
        $tests = ['Bench' => 'bench', 'Fuzz' => 'fuzz', 'Spec' => 'spec', 'Tests' => 'tests'];
        foreach ((new DeptracRules())->sequence($value, 'deptrac.layers') as $layer) {
            if (!$layer instanceof stdClass || !is_string($layer->name ?? null) || isset($seen[$layer->name])) {
                throw new PolicyException('deptrac.layers must contain unique named layers. Correct missing or duplicate layer names before running guard fix.');
            }
            $seen[$layer->name] = true;
            if (isset($tests[$layer->name])) {
                $layer->collectors = $this->testCollectors($layer->name, $tests[$layer->name]);
            } elseif ($layer->name === 'Src') {
                $layer->collectors = $this->fallbackCollectors($sources);
            } elseif (in_array($layer->name, $sources, true)) {
                $layer->collectors = $this->sourceCollectors($layer->collectors ?? null);
            }
            $layers[] = $layer;
        }
        foreach ($sources as $source) {
            if (isset($seen[$source])) {
                continue;
            }
            if ($source !== 'Src') {
                throw new PolicyException('Production layer "' . $source . '" is missing from deptrac.layers. Define its collectors or correct test_dependencies.source_layers in guard.yaml.');
            }
            $layers[] = (object) ['name' => 'Src', 'collectors' => $this->fallbackCollectors($sources)];
        }
        foreach ($active as $directory => $name) {
            if (!isset($seen[$name])) {
                $layers[] = (object) ['name' => $name, 'collectors' => $this->testCollectors($name, $directory)];
            }
        }
        return $layers;
    }

    /**
     * Collects production code not assigned to a more specific production layer.
     * @param list<string> $sources
     * @return list<stdClass>
     */
    public function fallbackCollectors(array $sources): array
    {
        $directory = (object) ['type' => 'directory', 'value' => '^\\Q%projectDirectory%\\E/src/.*'];
        $excluded = [];
        foreach ($sources as $source) {
            if ($source !== 'Src') {
                $excluded[] = (object) ['type' => 'layer', 'value' => $source];
            }
        }
        return $excluded === [] ? [$directory] : [(object) ['type' => 'bool', 'must' => [$directory], 'must_not' => $excluded]];
    }

    /**
     * Matches namespace members and standalone files such as fuzz entrypoints.
     * @return list<stdClass>
     */
    public function testCollectors(string $name, string $directory): array
    {
        return [(object) ['type' => 'classLike', 'value' => '^' . $name . '\\\\'], (object) ['type' => 'directory', 'value' => '^\\Q%projectDirectory%\\E/' . $directory . '/.*']];
    }

    /**
     * Intersects custom production collectors with src/ without broadening their match.
     * @return list<stdClass>
     * @throws PolicyException when a production collector is malformed
     */
    public function sourceCollectors(mixed $value): array
    {
        $result = [];
        foreach ((new DeptracRules())->sequence($value, 'production layer collectors') as $collector) {
            if (!$collector instanceof stdClass) {
                throw new PolicyException('Production layer collectors must be mappings. Define a directory or classLike collector for each production layer.');
            }
            $directory = (object) ['type' => 'directory', 'value' => '^\\Q%projectDirectory%\\E/src/.*'];
            $first = is_array($collector->must ?? null) ? ($collector->must[0] ?? null) : null;
            if ((($collector->type ?? null) === 'directory' && ($collector->value ?? null) === '^\\Q%projectDirectory%\\E/src/.*')
                || (($collector->type ?? null) === 'bool' && $first instanceof stdClass && ($first->type ?? null) === 'directory' && ($first->value ?? null) === '^\\Q%projectDirectory%\\E/src/.*')) {
                $result[] = $collector;
                continue;
            }
            $result[] = (object) ['type' => 'bool', 'must' => [$directory, $collector]];
        }
        if ($result === []) {
            throw new PolicyException('Production layers need at least one collector. Define the src/ classes each layer contains.');
        }
        return $result;
    }
}

<?php

declare(strict_types=1);

namespace Guard\Policy\TestDependency;

use Guard\Diagnostic\PolicyException;
use Guard\Policy\Rule;
use Guard\Structure\Document\DataDocument;
use JsonException;
use stdClass;

/**
 * Preserves production architecture while closing dependency edges between test layers.
 */
final class DeptracRules
{
    /**
     * Builds the paths, collectors, and allowed edges of the dependency graph.
     * @param array<string, string> $active
     * @param list<string> $sources
     * @return list<Rule>
     * @throws JsonException
     * @throws PolicyException when a named production layer is missing or the config is malformed
     */
    public function rules(TestDependencyRules $factory, string $file, array $active, array $sources, DataDocument $document): array
    {
        $paths = $this->sequence($document->read('/deptrac/paths')->value, 'deptrac.paths');
        foreach (array_merge(['src'], array_keys($active)) as $path) {
            if (!in_array($path, $paths, true) && !in_array('./' . $path, $paths, true) && !in_array($path . '/', $paths, true) && !in_array('./' . $path . '/', $paths, true)) {
                $paths[] = $path;
            }
        }
        $layers = (new DeptracLayers())->build($document->read('/deptrac/layers')->value, $active, $sources);
        $edges = $document->read('/deptrac/ruleset')->value ?? new stdClass();
        if (!$edges instanceof stdClass) {
            throw new PolicyException('deptrac.ruleset must be a mapping of layer names to dependency lists. Replace the malformed ruleset before running guard fix.');
        }
        $tests = array_values($factory->directories());
        foreach (get_object_vars($edges) as $name => $allowed) {
            $kept = [];
            foreach ($this->sequence($allowed, 'deptrac.ruleset.' . $name) as $target) {
                if (!is_string($target)) {
                    throw new PolicyException('deptrac.ruleset.' . $name . ' must contain layer names. Replace non-string entries.');
                }
                if (!in_array(ltrim($target, '+'), $tests, true)) {
                    $kept[] = $target;
                }
            }
            $edges->{$name} = $kept;
        }
        $edges->Src ??= array_values(array_filter($sources, static fn (string $source): bool => $source !== 'Src'));
        foreach ($layers as $layer) {
            $name = $layer->name;
            if (is_string($name) && in_array($name, $tests, true)) {
                $edges->{$name} = $sources;
            }
        }
        $types = $this->sequence($document->read('/deptrac/analyser/types')->value, 'deptrac.analyser.types');
        foreach (['class', 'use', 'file', 'function', 'function_call'] as $type) {
            if (!in_array($type, $types, true)) {
                $types[] = $type;
            }
        }
        $rules = [
            $factory->equal($file, 'deptrac.analyser', '/deptrac/analyser/types', $types, 'Deptrac must analyse class dependencies and standalone test or fuzz files.'),
            $factory->equal($file, 'deptrac.paths', '/deptrac/paths', $paths, 'Deptrac must analyse src/ and every existing test directory.'),
            $factory->equal($file, 'deptrac.layers', '/deptrac/layers', $layers, 'Deptrac must collect independent Bench, Fuzz, Spec, and Tests layers and confine production layers to src/.'),
            $factory->equal($file, 'deptrac.ruleset', '/deptrac/ruleset', $edges, 'Test layers may depend only on production layers; no other layer may depend on a test layer.'),
        ];
        return array_merge($rules, $this->bypasses($factory, $file, $document));
    }

    /**
     * Prevents exclusions, baselines and imported overrides from bypassing the boundaries.
     * @return list<Rule>
     * @throws JsonException
     */
    public function bypasses(TestDependencyRules $factory, string $file, DataDocument $document): array
    {
        $rules = [];
        $imports = $document->read('/imports')->value;
        $rules[] = new Rule('test-dependencies.deptrac.imports', $file, 'yaml', '/imports', 'required', ['equals' => []], [], $imports === null || $imports === [], 'Deptrac imports in {file} can override the test boundaries. Inline the imported layers, ruleset, and baseline into {file}, then set imports to [] so Guard can inspect the complete configuration.');
        $rules[] = $factory->equal($file, 'deptrac.exclude-files', '/deptrac/exclude_files', [], 'Deptrac exclusions can hide forbidden test dependencies. Analyse all files in the configured paths.');
        $skips = $document->read('/deptrac/skip_violations')->value ?? new stdClass();
        if ($skips === []) {
            $skips = new stdClass();
        }
        if ($skips instanceof stdClass) {
            foreach (get_object_vars($skips) as $from => $targets) {
                if ($this->testNamespace($from)) {
                    unset($skips->{$from});
                    continue;
                }
                if (is_array($targets)) {
                    $kept = [];
                    foreach ($targets as $target) {
                        if (is_string($target) && !$this->testNamespace($target)) {
                            $kept[] = $target;
                        }
                    }
                    $skips->{$from} = $kept;
                }
            }
            $rules[] = $factory->equal($file, 'deptrac.skip-violations', '/deptrac/skip_violations', $skips, 'Deptrac must not suppress violations involving Bench, Fuzz, Spec, or Tests namespaces.');
        }
        return $rules;
    }

    /**
     * Reports whether a token belongs to one of the reserved test namespaces.
     */
    public function testNamespace(string $name): bool
    {
        return preg_match('/^(Bench|Fuzz|Spec|Tests)\\\\/i', ltrim($name, '\\')) === 1;
    }

    /**
     * @return list<mixed>
     * @throws PolicyException when a Deptrac sequence is malformed
     */
    public function sequence(mixed $value, string $name): array
    {
        if ($value === null) {
            return [];
        }
        if (!is_array($value) || array_values($value) !== $value) {
            throw new PolicyException($name . ' must be a list. Correct the Deptrac configuration before running guard fix.');
        }
        return $value;
    }
}

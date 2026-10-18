<?php

declare(strict_types=1);

namespace DecisionsLint\Config;

use DecisionsLint\Diagnostic\LintException;

/**
 * Loads decisions.yaml, the rule files it imports, and lays the project's rule overrides over them.
 *
 * Imports are read in order and a later file overrides an earlier definition
 * of the same rule id. The rules written in decisions.yaml override all of them.
 */
final class ImportResolver
{
    /**
     * Creates a resolver, optionally with shared collaborators.
     */
    public function __construct(private ?DocumentLoader $loader = null, private ?DocumentMerger $merger = null)
    {
    }

    /**
     * Returns the configuration document with its `rules` replaced by the merged rule definitions.
     *
     * Every merged rule carries a `source` key naming the files that defined it, for messages.
     *
     * @return array<string, mixed>
     * @throws LintException when a file is missing, malformed, or departs from its schema
     */
    public function resolve(string $configPath): array
    {
        $loader = $this->loader ?? new DocumentLoader();
        $document = $loader->load($configPath, SchemaCatalog::DECISIONS, basename($configPath));
        $directory = dirname($configPath);
        $rules = [];
        foreach ($this->strings($document['imports'] ?? null) as $import) {
            $path = str_starts_with($import, '/') ? $import : $directory . '/' . $import;
            $file = $loader->load($path, SchemaCatalog::RULES, $import);
            $rules = $this->overlay($rules, $this->mapping($file['rules'] ?? null), $import);
        }
        $document['rules'] = $this->overlay($rules, $this->mapping($document['rules'] ?? null), basename($configPath));

        return $document;
    }

    /**
     * Lays every rule of a file over the rules collected so far.
     *
     * @param array<string, array<string, mixed>> $rules
     * @param array<string, mixed> $definitions
     * @return array<string, array<string, mixed>>
     */
    public function overlay(array $rules, array $definitions, string $source): array
    {
        $merger = $this->merger ?? new DocumentMerger();
        foreach ($definitions as $id => $definition) {
            $existing = $rules[$id] ?? ['source' => []];
            $merged = $merger->merge($existing, $this->mapping($definition));
            $merged['source'] = array_merge($this->strings($existing['source'] ?? null), [$source]);
            $rules[$id] = $merged;
        }

        return $rules;
    }

    /**
     * Reads a value as a mapping with string keys, or an empty mapping.
     *
     * @param mixed $value
     * @return array<string, mixed>
     */
    public function mapping($value): array
    {
        $mapping = [];
        foreach (is_array($value) ? $value : [] as $key => $entry) {
            $mapping[(string) $key] = $entry;
        }

        return $mapping;
    }

    /**
     * Reads a value as a list of strings, or an empty list.
     *
     * @param mixed $value
     * @return list<string>
     */
    public function strings($value): array
    {
        $strings = [];
        foreach (is_array($value) ? $value : [] as $entry) {
            if (is_string($entry)) {
                $strings[] = $entry;
            }
        }

        return $strings;
    }
}

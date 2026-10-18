<?php

declare(strict_types=1);

namespace DecisionsLint\Config;

use DecisionsLint\Diagnostic\LintException;

/**
 * Reads decisions.yaml and its imports into a Configuration.
 */
final class ConfigurationLoader
{
    /**
     * Creates a loader, optionally with shared collaborators.
     */
    public function __construct(private ?ImportResolver $resolver = null, private ?RuleReader $rules = null, private ?ProviderReader $provider = null)
    {
    }

    /**
     * Loads the configuration at a path.
     *
     * @throws LintException when a file is missing, malformed, departs from its schema, or a rule is incomplete
     */
    public function load(string $path): Configuration
    {
        $document = ($this->resolver ?? new ImportResolver())->resolve($path);
        $reader = $this->rules ?? new RuleReader();
        $rules = [];
        foreach ($this->mapping($document['rules'] ?? null) as $id => $definition) {
            $rules[] = $reader->read($id, $this->mapping($definition));
        }
        $failOn = $document['fail_on'] ?? 'error';
        $paths = $this->strings($document['paths'] ?? null);

        return new Configuration(
            $path,
            ($this->provider ?? new ProviderReader())->read($this->mapping($document['provider'] ?? null)),
            $paths === [] ? ['src'] : $paths,
            $this->strings($document['exclude'] ?? null),
            is_string($failOn) ? $failOn : 'error',
            $rules,
        );
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

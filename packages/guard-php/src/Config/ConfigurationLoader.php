<?php

declare(strict_types=1);

namespace Guard\Config;

use Guard\Policy\PolicyException;
use JsonException;

/**
 * Loads the versioned guard.yaml project policy.
 */
final class ConfigurationLoader
{
    /**
     * @throws PolicyException when guard.yaml is missing or has an unsupported schema
     * @throws JsonException
     */
    public function load(string $path): Configuration
    {
        $data = (new ImportResolver())->resolve($path);
        $root = dirname($path);
        return new Configuration(
            $root,
            array_key_exists('metrics', $data) ? (new MetricsReader())->read($data['metrics'], $root) : null,
            array_key_exists('structure', $data) ? (new StructureReader())->read($data['structure'], $root) : null,
            array_key_exists('documentation', $data) ? (new DocumentationReader())->read($data['documentation'], $root, basename($path)) : null,
            (new RuleReader())->read($data['configuration'] ?? []),
        );
    }
}

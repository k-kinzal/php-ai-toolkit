<?php

declare(strict_types=1);

namespace Toolkit\Guard\Config;

use JsonException;
use Toolkit\Guard\Policy\PolicyException;

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
        $scope = (new Schema())->mapping($data['scope'] ?? [], ['source', 'exclude'], 'scope');
        $root = dirname($path);
        return new Configuration(
            $root,
            array_key_exists('quality', $data) ? (new QualityReader())->read($data['quality'], $scope, $root) : null,
            array_key_exists('structure', $data) ? (new StructureReader())->read($data['structure'], $root) : null,
            array_key_exists('documentation', $data) ? (new DocumentationReader())->read($data['documentation'], $root, basename($path)) : null,
            (new RuleReader())->read($data['configuration'] ?? []),
        );
    }
}

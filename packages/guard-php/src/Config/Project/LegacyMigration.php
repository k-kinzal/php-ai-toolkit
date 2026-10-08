<?php

declare(strict_types=1);

namespace Guard\Config\Project;

use Guard\Config\Project\Legacy\DirectoryConfiguration as DirectoryLoader;
use Guard\Config\Project\Legacy\HeadingConfiguration as HeadingLoader;
use Guard\Config\Project\Legacy\MetricConfiguration as MetricLoader;
use Symfony\Component\Yaml\Yaml;

/**
 * Imports existing guard policies without changing thresholds or enabled constraints.
 */
final class LegacyMigration
{
    /**
     * @param array<string, mixed> $defaults
     * @param bool $includeProjectRoot include the project root for presets that check root directory entries
     * @return array<string, mixed>
     */
    public function migrate(string $root, array $defaults, bool $includeProjectRoot = false): array
    {
        if (is_file($root . '/loc.yaml')) {
            (new MetricLoader())->load($root . '/loc.yaml');
            $data = $this->read($root . '/loc.yaml');
            $scan = is_array($data['scan'] ?? null) ? $data['scan'] : [];
            $apply = is_array($data['apply'] ?? null) ? $data['apply'] : [];
            $defaults['metrics'] = [
                'source' => $scan['roots'] ?? [],
                'exclude' => $scan['exclude'] ?? [],
                'profiles' => $data['policies'],
                'default' => $apply['default'],
                'assignments' => $apply['rules'] ?? [],
            ];
        }
        if (is_file($root . '/tree.yaml')) {
            $config = (new DirectoryLoader())->load($root . '/tree.yaml');
            $data = $this->read($root . '/tree.yaml');
            $paths = $config->paths;
            if ($includeProjectRoot && !in_array('.', $paths, true)) {
                $paths[] = '.';
            }
            $defaults['structure'] = ['paths' => $paths, 'exclude' => $data['exclude'] ?? [], 'directories' => $data['rules'] ?? []];
        }
        if (is_file($root . '/doc-guard.yaml')) {
            (new HeadingLoader())->load($root . '/doc-guard.yaml');
            $data = $this->read($root . '/doc-guard.yaml');
            $defaults['documentation'] = ['files' => $data['documents'], 'scan' => $data['scan'] ?? []];
        }
        return $defaults;
    }
    /**
     * @return array<string, mixed>
     */
    public function read(string $path): array
    {
        $data = Yaml::parseFile($path);
        $mapping = [];
        foreach (is_array($data) ? $data : [] as $key => $value) {
            if (is_string($key)) {
                $mapping[$key] = $value;
            }
        }
        return $mapping;
    }
}

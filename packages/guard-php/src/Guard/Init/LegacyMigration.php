<?php

declare(strict_types=1);

namespace Toolkit\Guard\Init;

use Symfony\Component\Yaml\Yaml;
use Toolkit\DocGuard\Config\ConfigLoader as DocLoader;
use Toolkit\LocGuard\Config\ConfigLoader as LocLoader;
use Toolkit\TreeGuard\Config\ConfigLoader as TreeLoader;

/**
 * Imports existing guard policies without changing thresholds or enabled constraints.
 */
final class LegacyMigration
{
    /**
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    public function migrate(string $root, array $defaults): array
    {
        if (is_file($root . '/loc.yaml')) {
            (new LocLoader())->load($root . '/loc.yaml');
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
            (new TreeLoader())->load($root . '/tree.yaml');
            $data = $this->read($root . '/tree.yaml');
            $defaults['structure'] = ['paths' => $data['paths'] ?? ['src'], 'exclude' => $data['exclude'] ?? [], 'directories' => $data['rules'] ?? []];
        }
        if (is_file($root . '/doc-guard.yaml')) {
            (new DocLoader())->load($root . '/doc-guard.yaml');
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

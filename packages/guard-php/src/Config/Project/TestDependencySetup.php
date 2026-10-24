<?php

declare(strict_types=1);

namespace Guard\Config\Project;

use Symfony\Component\Yaml\Yaml;

/**
 * Records the existing Deptrac source layers when initializing the dependency preset.
 */
final class TestDependencySetup
{
    /**
     * Selects a YAML filename and source directory layers without changing the tool configuration.
     * @return array<string, mixed>
     */
    public function settings(string $root): array
    {
        $file = (new Recommendations())->existing($root, ['deptrac.yaml', 'deptrac.yml']) ?? 'deptrac.yaml';
        $data = is_file($root . '/' . $file) ? Yaml::parseFile($root . '/' . $file) : [];
        $layers = is_array($data) && is_array($data['deptrac'] ?? null) ? ($data['deptrac']['layers'] ?? []) : [];
        return ['deptrac' => $file, 'source_layers' => $this->sourceLayers($layers)];
    }

    /**
     * Finds source directory layers in an existing Deptrac layer list.
     * @return list<string>
     */
    public function sourceLayers(mixed $layers): array
    {
        $sources = [];
        foreach (is_array($layers) ? $layers : [] as $layer) {
            if (!is_array($layer) || !is_string($layer['name'] ?? null) || !is_array($layer['collectors'] ?? null) || in_array($layer['name'], ['Bench', 'Fuzz', 'Spec', 'Tests'], true)) {
                continue;
            }
            foreach ($layer['collectors'] as $collector) {
                if (is_array($collector) && ($collector['type'] ?? null) === 'directory' && is_string($collector['value'] ?? null) && preg_match('~^\^?src/~', $collector['value']) === 1) {
                    $sources[] = $layer['name'];
                    break;
                }
            }
        }
        return $sources === [] ? ['Src'] : $sources;
    }
}

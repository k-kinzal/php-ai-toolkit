<?php

declare(strict_types=1);

namespace Guard\Config\Project;

use JsonException;

/**
 * Discovers tools and source roots from the project's own Composer files.
 */
final class ToolDetector
{
    /**
     * @return array<string, mixed>
     * @throws JsonException when a Composer file is malformed
     * @throws JsonException
     */
    public function manifest(string $root, string $file = 'composer.json'): array
    {
        $source = is_file($root . '/' . $file) ? file_get_contents($root . '/' . $file) : false;
        $data = $source === false ? [] : json_decode($source, true, 512, JSON_THROW_ON_ERROR);
        $mapping = [];
        foreach (is_array($data) ? $data : [] as $key => $value) {
            if (is_string($key)) {
                $mapping[$key] = $value;
            }
        }
        return $mapping;
    }
    /**
     * @return array<string, string>
     * @throws JsonException
     */
    public function packages(string $root): array
    {
        $manifest = $this->manifest($root);
        $packages = [];
        foreach (['require', 'require-dev'] as $section) {
            if (!isset($manifest[$section]) || !is_array($manifest[$section])) {
                continue;
            }
            foreach ($manifest[$section] as $name => $version) {
                if (is_string($name) && is_string($version)) {
                    $packages[$name] = $version;
                }
            }
        }
        $locked = $this->manifest($root, 'composer.lock');
        foreach (['packages', 'packages-dev'] as $section) {
            $entries = $locked[$section] ?? [];
            foreach (is_array($entries) ? $entries : [] as $entry) {
                if (is_array($entry) && is_string($entry['name'] ?? null) && is_string($entry['version'] ?? null)) {
                    $packages[$entry['name']] = ltrim($entry['version'], 'v');
                }
            }
        }
        return $packages;
    }
    /**
     * @return list<string>
     * @throws JsonException
     */
    public function sources(string $root): array
    {
        $manifest = $this->manifest($root);
        $autoload = $manifest['autoload'] ?? [];
        $mappings = is_array($autoload) ? ($autoload['psr-4'] ?? []) : [];
        $paths = [];
        foreach (is_array($mappings) ? $mappings : [] as $entry) {
            foreach (is_array($entry) ? $entry : [$entry] as $path) {
                if (is_string($path) && $path !== '' && is_dir($root . '/' . $path)) {
                    $paths[] = rtrim($path, '/');
                }
            }
        }
        if ($paths === [] && is_dir($root . '/src')) {
            $paths[] = 'src';
        }
        return array_values(array_unique($paths));
    }
}

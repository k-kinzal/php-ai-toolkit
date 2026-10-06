<?php

declare(strict_types=1);

namespace Guard\Init;

use Guard\Policy\PolicyException;
use JsonException;

/**
 * Writes the project-specific differences from the shipped presets.
 */
final class PresetOverrides
{
    /**
     * Adds directory rules for non-src roots and file-path overrides for detected tool configs.
     *
     * @param list<string> $names
     * @param array<string, mixed> $document
     * @return array<string, mixed>
     * @throws JsonException when a preset cannot be read
     * @throws PolicyException when a selected preset is missing a rule id
     */
    public function apply(string $root, array $names, array $document): array
    {
        $directories = $this->directories($root, $names);
        if ($directories !== []) {
            $document['structure'] = ['directories' => $directories];
        }
        $rules = $this->files($root, $names);
        if ($rules !== []) {
            $document['configuration'] = $rules;
        }

        return $document;
    }

    /**
     * Returns directory rules for source roots other than src.
     *
     * The structure preset already covers src. These rules use the same constraints for each additional root.
     *
     * @param list<string> $names
     * @return list<non-empty-array<mixed>>
     * @throws JsonException when composer.json is malformed
     */
    public function directories(string $root, array $names): array
    {
        if (!in_array('structure', $names, true)) {
            return [];
        }
        $sources = [];
        foreach ((new ToolDetector())->sources($root) as $source) {
            if ($source !== 'src') {
                $sources[] = $source;
            }
        }
        if ($sources === []) {
            return [];
        }
        $built = (new Initializer())->structure($sources, false);
        $directories = $built['directories'] ?? null;
        if (!is_array($directories)) {
            return [];
        }
        $rules = [];
        foreach ($directories as $rule) {
            if (is_array($rule) && is_string($rule['path'] ?? null) && !in_array($rule['path'], ['**', 'skills/*'], true)) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    /**
     * Returns id and file overrides when a detected config path differs from the preset.
     *
     * @param list<string> $names
     * @return list<array<string, mixed>>
     * @throws PolicyException when a selected preset is missing a rule id
     */
    public function files(string $root, array $names): array
    {
        $rules = [];
        $catalog = new PresetCatalog();
        foreach ($names as $name) {
            $default = $catalog->defaults()[$name] ?? null;
            $actual = $this->actual($root, $name);
            if (is_string($default) && is_string($actual) && $actual !== $default) {
                $rules = array_merge($rules, $this->refile($names, [$name], $actual));
            }
        }

        return array_merge($rules, $this->pbtExcludes($root, $names));
    }

    /**
     * Returns the configuration file that should receive one preset's rules.
     */
    public function actual(string $root, string $preset): ?string
    {
        $candidates = (new PresetCatalog())->candidates($preset);

        return $candidates === [] ? null : (new Recommendations())->existing($root, $candidates);
    }

    /**
     * Adds absence checks for a global PHPUnit pbt group exclude on each existing configuration file.
     *
     * @param list<string> $names
     * @return list<array<string, mixed>>
     */
    public function pbtExcludes(string $root, array $names): array
    {
        if (!in_array('pbt', $names, true)) {
            return [];
        }
        $files = [
            'phpunit.xml',
            'phpunit.xml.dist',
            'phpunit9.xml',
            'phpunit9.xml.dist',
            'phpunit10.xml',
            'phpunit10.xml.dist',
            'phpunit11.xml',
            'phpunit11.xml.dist',
            'phpunit12.xml',
            'phpunit12.xml.dist',
        ];
        $rules = [];
        foreach ($files as $file) {
            if (!is_file($root . '/' . $file)) {
                continue;
            }
            $rules[] = [
                'id' => 'pbt.exclude.' . str_replace('.', '-', $file),
                'file' => $file,
                'format' => 'xml',
                'select' => '/phpunit/groups/exclude/group[text()="pbt"]',
                'level' => 'required',
                'assert' => ['absent' => true],
            ];
        }

        return $rules;
    }

    /**
     * Points every rule in the selected presets at one detected file.
     *
     * @param list<string> $selected
     * @param list<string> $presets
     * @return list<array<string, string>>
     * @throws PolicyException when a preset is missing a rule id
     */
    public function refile(array $selected, array $presets, string $file): array
    {
        $rules = [];
        foreach ($presets as $preset) {
            if (!in_array($preset, $selected, true)) {
                continue;
            }
            foreach ((new PresetCatalog())->ruleIds($preset) as $id) {
                $rules[] = ['id' => $id, 'file' => $file];
            }
        }

        return $rules;
    }
}

<?php

declare(strict_types=1);

namespace Guard\Config;

/**
 * Overlays a later guard document onto an earlier one.
 *
 * Configuration rules and directory rules merge by identity. Metric limits merge by metric. Metric source and exclude replace when a later file sets them.
 */
final class DocumentMerger
{
    /**
     * Applies the overlay document after the base document.
     *
     * @param array<string, mixed> $base
     * @param array<string, mixed> $overlay
     * @return array<string, mixed>
     */
    public function merge(array $base, array $overlay): array
    {
        $merged = $base;
        if (array_key_exists('version', $overlay)) {
            $merged['version'] = $overlay['version'];
        }
        $merged = $this->mergeSection($merged, $overlay, 'metrics');
        $merged = $this->mergeSection($merged, $overlay, 'structure');
        $merged = $this->mergeSection($merged, $overlay, 'documentation');
        if (array_key_exists('collect', $overlay)) {
            (new Reader\ScopeConfigReader())->read($overlay['collect']);
            $current = (new Schema())->mapping($merged['collect'] ?? [], ['include', 'exclude'], 'collect');
            $scope = (new Schema())->mapping($overlay['collect'], ['include', 'exclude'], 'collect');
            $merged['collect'] = array_replace($current, $scope);
        }
        if (array_key_exists('extensions', $overlay)) {
            $extensions = (new Reader\ExtensionConfigReader())->read($overlay['extensions']);
            $current = (new Reader\ExtensionConfigReader())->read($merged['extensions'] ?? []);
            $merged['extensions'] = array_replace($current, $extensions);
        }
        if (!array_key_exists('configuration', $overlay)) {
            return $merged;
        }
        $rules = $overlay['configuration'];
        $current = $merged['configuration'] ?? [];
        $merged['configuration'] = is_array($rules) && $this->isList($rules)
            ? $this->entries(is_array($current) ? $current : [], $rules, 'id')
            : $rules;

        return $merged;
    }

    /**
     * Merges one named mapping section, or replaces it when the overlay is not a mapping.
     *
     * @param array<string, mixed> $merged
     * @param array<string, mixed> $overlay
     * @return array<string, mixed>
     */
    public function mergeSection(array $merged, array $overlay, string $name): array
    {
        if (!array_key_exists($name, $overlay)) {
            return $merged;
        }
        $section = $overlay[$name];
        $current = $merged[$name] ?? [];
        if (!is_array($section) || !$this->isMapping($section)) {
            $merged[$name] = $section;

            return $merged;
        }
        $base = is_array($current) && $this->isMapping($current) ? $current : [];
        if ($name === 'metrics') {
            $merged[$name] = $this->metrics($base, $section);
        } elseif ($name === 'structure') {
            $merged[$name] = $this->structure($base, $section);
        } else {
            $merged[$name] = $this->documentation($base, $section);
        }

        return $merged;
    }

    /**
     * Merges metric profiles by name and replaces source, exclude, default, or assignments when the overlay sets them.
     *
     * @param array<mixed> $base
     * @param array<mixed> $overlay
     * @return array<mixed>
     */
    public function metrics(array $base, array $overlay): array
    {
        $merged = $base;
        foreach (['source', 'exclude'] as $key) {
            if (array_key_exists($key, $overlay)) {
                $merged[$key] = $overlay[$key];
            }
        }
        if (isset($overlay['profiles']) && is_array($overlay['profiles']) && $this->isMapping($overlay['profiles'])) {
            $profiles = $merged['profiles'] ?? [];
            $merged['profiles'] = $this->profiles(is_array($profiles) ? $profiles : [], $overlay['profiles']);
        } elseif (array_key_exists('profiles', $overlay)) {
            $merged['profiles'] = $overlay['profiles'];
        }
        foreach (['default', 'assignments'] as $key) {
            if (array_key_exists($key, $overlay)) {
                $merged[$key] = $overlay[$key];
            }
        }

        return $merged;
    }

    /**
     * Merges profile definitions by profile name.
     *
     * @param array<mixed> $base
     * @param array<mixed> $overlay
     * @return array<mixed>
     */
    public function profiles(array $base, array $overlay): array
    {
        foreach ($overlay as $name => $profile) {
            if (!is_string($name) || !is_array($profile) || !$this->isMapping($profile)) {
                $base[$name] = $profile;
                continue;
            }
            $current = $base[$name] ?? [];
            $base[$name] = $this->profile(is_array($current) && $this->isMapping($current) ? $current : [], $profile);
        }

        return $base;
    }

    /**
     * Replaces profile keys and merges limit metrics inside an existing kind.
     *
     * @param array<mixed> $base
     * @param array<mixed> $overlay
     * @return array<mixed>
     */
    public function profile(array $base, array $overlay): array
    {
        $merged = $base;
        foreach ($overlay as $key => $value) {
            if ($key === 'limits' && is_array($value) && isset($merged['limits']) && is_array($merged['limits'])) {
                $merged['limits'] = $this->limits($merged['limits'], $value);
                continue;
            }
            $merged[$key] = $value;
        }

        return $merged;
    }

    /**
     * Replaces named limit metrics and keeps metrics the overlay does not mention.
     *
     * @param array<mixed> $base
     * @param array<mixed> $overlay
     * @return array<mixed>
     */
    public function limits(array $base, array $overlay): array
    {
        foreach ($overlay as $kind => $metrics) {
            if (!is_string($kind) || !is_array($metrics) || !isset($base[$kind]) || !is_array($base[$kind]) || !$this->isMapping($metrics) || !$this->isMapping($base[$kind])) {
                $base[$kind] = $metrics;
                continue;
            }
            $base[$kind] = $this->patch($base[$kind], $metrics);
        }

        return $base;
    }

    /**
     * Replaces structure paths and exclude lists, and merges directory rules by path.
     *
     * @param array<mixed> $base
     * @param array<mixed> $overlay
     * @return array<mixed>
     */
    public function structure(array $base, array $overlay): array
    {
        $merged = $base;
        foreach (['paths', 'exclude'] as $key) {
            if (array_key_exists($key, $overlay)) {
                $merged[$key] = $overlay[$key];
            }
        }
        if (!array_key_exists('directories', $overlay)) {
            return $merged;
        }
        $directories = $overlay['directories'];
        $current = $merged['directories'] ?? [];
        $merged['directories'] = is_array($directories) && $this->isList($directories)
            ? $this->entries(is_array($current) ? $current : [], $directories, 'path')
            : $directories;

        return $merged;
    }

    /**
     * Replaces one declared document at a time and replaces scan or exclude when set.
     *
     * @param array<mixed> $base
     * @param array<mixed> $overlay
     * @return array<mixed>
     */
    public function documentation(array $base, array $overlay): array
    {
        $merged = $base;
        if (array_key_exists('files', $overlay)) {
            $files = $overlay['files'];
            $current = $merged['files'] ?? [];
            $merged['files'] = is_array($files) && $this->isMapping($files)
                ? $this->files(is_array($current) && $this->isMapping($current) ? $current : [], $files)
                : $files;
        }
        foreach (['scan', 'exclude'] as $key) {
            if (array_key_exists($key, $overlay)) {
                $merged[$key] = $overlay[$key];
            }
        }

        return $merged;
    }

    /**
     * Replaces declared documents by path.
     *
     * @param array<mixed> $base
     * @param array<mixed> $overlay
     * @return array<mixed>
     */
    public function files(array $base, array $overlay): array
    {
        foreach ($overlay as $path => $document) {
            $base[$path] = $document;
        }

        return $base;
    }

    /**
     * Merges list entries by a string identity field. Later keys replace earlier keys. New identities append.
     *
     * @param array<mixed> $base
     * @param array<mixed> $overlay
     * @return list<mixed>
     */
    public function entries(array $base, array $overlay, string $key): array
    {
        [$merged, $index] = $this->index($base, $key);
        foreach ($overlay as $rule) {
            $name = $this->entryName($rule, $key);
            if ($name === null || !isset($index[$name]) || !is_array($rule)) {
                $merged[] = $rule;
                if ($name !== null) {
                    $index[$name] = count($merged) - 1;
                }
                continue;
            }
            $current = $merged[$index[$name]];
            $merged[$index[$name]] = $this->patch(is_array($current) ? $current : [], $rule);
        }

        return $merged;
    }

    /**
     * Indexes list entries by a string field, keeping the first position of each name.
     *
     * @param array<mixed> $rules
     * @return array{0: list<mixed>, 1: array<string, int>}
     */
    public function index(array $rules, string $key): array
    {
        $merged = [];
        $index = [];
        foreach ($rules as $rule) {
            $merged[] = $rule;
            $name = $this->entryName($rule, $key);
            if ($name !== null) {
                $index[$name] = count($merged) - 1;
            }
        }

        return [$merged, $index];
    }

    /**
     * Returns the string identity of one list entry.
     *
     * @param mixed $rule
     */
    public function entryName($rule, string $key): ?string
    {
        if (!is_array($rule) || !is_string($rule[$key] ?? null) || $rule[$key] === '') {
            return null;
        }

        return $rule[$key];
    }

    /**
     * Replaces keys present in the overlay and keeps the other base keys.
     *
     * @param array<mixed> $base
     * @param array<mixed> $overlay
     * @return array<mixed>
     */
    public function patch(array $base, array $overlay): array
    {
        foreach ($overlay as $key => $value) {
            $base[$key] = $value;
        }

        return $base;
    }

    /**
     * Reports whether the value is a YAML mapping rather than a list.
     *
     * @param mixed $value
     */
    public function isMapping($value): bool
    {
        return is_array($value) && ($value === [] || array_values($value) !== $value);
    }

    /**
     * Reports whether the value is a YAML list.
     *
     * @param mixed $value
     */
    public function isList($value): bool
    {
        return is_array($value) && array_values($value) === $value;
    }
}

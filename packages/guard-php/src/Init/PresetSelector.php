<?php

declare(strict_types=1);

namespace Guard\Init;

use Guard\Policy\PolicyException;
use JsonException;

/**
 * Chooses which shipped presets a project imports.
 */
final class PresetSelector
{
    /**
     * Uses an explicit name list, or the presets that match the project.
     *
     * loc.yaml and tree.yaml suppress the metrics and structure presets because those files are copied in full.
     *
     * @param ?list<string> $imports
     * @return list<string>
     * @throws JsonException when composer.json or composer.lock is malformed
     * @throws PolicyException when an explicit name is not a shipped preset
     */
    public function select(string $root, ?array $imports): array
    {
        $names = $this->unique($imports ?? $this->detect($root));
        foreach ($names as $name) {
            (new PresetCatalog())->assertKnown($name);
        }
        if (is_file($root . '/loc.yaml')) {
            $names = $this->without($names, 'metrics');
        }
        if (is_file($root . '/tree.yaml')) {
            $names = $this->without($names, 'structure');
        }

        return $names;
    }

    /**
     * Detects generic presets from source roots and docs presence, and tool presets from installed configuration.
     *
     * @return list<string>
     * @throws JsonException when composer.json or composer.lock is malformed
     */
    public function detect(string $root): array
    {
        $names = [];
        if ((new ToolDetector())->sources($root) !== []) {
            $names[] = 'metrics';
            $names[] = 'structure';
        }
        if (!is_dir($root . '/docs')) {
            $names[] = 'disable-doc';
        }
        foreach ($this->phpstanPresets($root) as $name) {
            $names[] = $name;
        }
        $phpunit = $this->phpunitPresets($root);
        foreach ($phpunit as $name) {
            $names[] = $name;
        }
        foreach ($this->doctestPresets($root, $phpunit) as $name) {
            $names[] = $name;
        }
        foreach ($this->toolPresets($root) as $name) {
            $names[] = $name;
        }
        if (is_file($root . '/composer.json')) {
            $names[] = 'composer';
        }

        return $names;
    }

    /**
     * Returns PHPStan presets when a NEON file exists, including toolkit rules when that package is installed.
     *
     * @return list<string>
     * @throws JsonException when composer.json or composer.lock is malformed
     */
    public function phpstanPresets(string $root): array
    {
        if ((new Recommendations())->existing($root, ['phpstan.neon', 'phpstan.neon.dist']) === null) {
            return [];
        }
        $names = ['phpstan'];
        $packages = (new ToolDetector())->packages($root);
        if (isset($packages['k-kinzal/phpstan-guard-rules']) || isset($packages['k-kinzal/php-ai-toolkit'])) {
            $names[] = 'phpstan-guard-rules';
        }

        return $names;
    }

    /**
     * Returns one PHPUnit preset for each versioned file, or the single resolved major.
     *
     * An unversioned file is PHPUnit 13 when versioned files also exist. A multi-major constraint
     * with no lock and no versioned files is not guessed.
     *
     * @return list<string>
     * @throws JsonException when composer.json or composer.lock is malformed
     */
    public function phpunitPresets(string $root): array
    {
        $versioned = [
            'phpunit9' => ['phpunit9.xml', 'phpunit9.xml.dist'],
            'phpunit10' => ['phpunit10.xml', 'phpunit10.xml.dist'],
            'phpunit11' => ['phpunit11.xml', 'phpunit11.xml.dist'],
            'phpunit12' => ['phpunit12.xml', 'phpunit12.xml.dist'],
        ];
        $names = [];
        foreach ($versioned as $name => $candidates) {
            if ((new Recommendations())->existing($root, $candidates) !== null) {
                $names[] = $name;
            }
        }
        $plain = (new Recommendations())->existing($root, ['phpunit.xml', 'phpunit.xml.dist']);
        if ($names !== []) {
            if ($plain !== null) {
                $names[] = 'phpunit13';
            }

            return $names;
        }
        if ($plain === null) {
            return [];
        }
        $major = $this->phpunitMajor((new ToolDetector())->packages($root)['phpunit/phpunit'] ?? '');
        $preset = $major === null ? null : ($major === '13' ? 'phpunit13' : 'phpunit' . $major);
        if ($preset === null || !in_array($preset, (new PresetCatalog())->names(), true)) {
            return [];
        }

        return [$preset];
    }

    /**
     * Returns the only PHPUnit major in a lock version or a single-major constraint.
     */
    public function phpunitMajor(string $version): ?string
    {
        if (preg_match('/^(\d+)\.\d+/', $version, $locked) === 1) {
            return $locked[1];
        }
        $parts = preg_split('/\s*\|\|\s*/', $version);
        $majors = [];
        foreach (is_array($parts) ? $parts : [] as $part) {
            if (preg_match('/(\d+)/', $part, $match) === 1) {
                $majors[$match[1]] = $match[1];
            }
        }

        return count($majors) === 1 ? array_values($majors)[0] : null;
    }

    /**
     * Returns doctest presets for PHPUnit files that already run examples, or when PHPStan requires them.
     *
     * @param list<string> $phpunit
     * @return list<string>
     */
    public function doctestPresets(string $root, array $phpunit): array
    {
        $map = [
            'phpunit9' => 'doctest9',
            'phpunit10' => 'doctest10',
            'phpunit11' => 'doctest11',
            'phpunit12' => 'doctest12',
            'phpunit13' => 'doctest13',
        ];
        $examples = $this->requiresExamples($root);
        $names = [];
        foreach ($phpunit as $preset) {
            $doctest = $map[$preset] ?? null;
            $file = $doctest === null ? null : (new PresetOverrides())->actual($root, $preset);
            $source = is_string($file) ? file_get_contents($root . '/' . $file) : false;
            if ($doctest !== null && $source !== false && (str_contains($source, 'DoctestSuite') || $examples)) {
                $names[] = $doctest;
            }
        }

        return $names;
    }

    /**
     * Reports whether PHPStan is configured to require PHPDoc examples.
     */
    public function requiresExamples(string $root): bool
    {
        $file = (new Recommendations())->existing($root, ['phpstan.neon', 'phpstan.neon.dist']);
        $source = $file === null ? false : file_get_contents($root . '/' . $file);

        return is_string($source) && (str_contains($source, 'allRules: true') || str_contains($source, 'requireExample'));
    }

    /**
     * Returns tool presets whose configuration or package is present.
     *
     * @return list<string>
     * @throws JsonException when composer.json or composer.lock is malformed
     */
    public function toolPresets(string $root): array
    {
        $names = [];
        $packages = (new ToolDetector())->packages($root);
        $existing = new Recommendations();
        if ($existing->existing($root, ['.php-cs-fixer.dist.php', '.php-cs-fixer.php']) !== null) {
            $names[] = 'php-cs-fixer';
        }
        if ($existing->existing($root, ['phpcs.xml.dist', 'phpcs.xml']) !== null) {
            $names[] = 'phpcs';
        }
        if (isset($packages['deptrac/deptrac']) && $existing->existing($root, ['deptrac.yaml', 'deptrac.yml']) !== null) {
            $names[] = 'deptrac';
        }
        if (is_file($root . '/infection.json5')) {
            $names[] = 'infection';
        }
        if (is_file($root . '/.github/workflows/ci.yml')) {
            $names[] = 'github-actions';
        }
        if (is_file($root . '/.github/workflows/mutation.yml')) {
            $names[] = 'mutation';
        }
        if (isset($packages['giorgiosironi/eris'])) {
            $names[] = 'pbt';
        }
        if (isset($packages['nikic/php-fuzzer'])) {
            $names[] = 'fuzz';
        }
        if (isset($packages['phpbench/phpbench'])) {
            $names[] = 'phpbench';
        }
        if (isset($packages['k-kinzal/docgen-php']) || $this->packageName($root) === 'k-kinzal/docgen-php') {
            $names[] = 'docgen';
        }

        return $names;
    }

    /**
     * Returns the composer package name.
     *
     * @throws JsonException when composer.json is malformed
     */
    public function packageName(string $root): string
    {
        $name = (new ToolDetector())->manifest($root)['name'] ?? '';

        return is_string($name) ? $name : '';
    }

    /**
     * Drops repeated names while keeping the first occurrence.
     *
     * @param list<string> $names
     * @return list<string>
     */
    public function unique(array $names): array
    {
        $unique = [];
        foreach ($names as $name) {
            if (!in_array($name, $unique, true)) {
                $unique[] = $name;
            }
        }

        return $unique;
    }

    /**
     * Returns the names except one suppressed preset.
     *
     * @param list<string> $names
     * @return list<string>
     */
    public function without(array $names, string $suppressed): array
    {
        $remaining = [];
        foreach ($names as $name) {
            if ($name !== $suppressed) {
                $remaining[] = $name;
            }
        }

        return $remaining;
    }
}

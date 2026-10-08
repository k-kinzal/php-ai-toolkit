<?php

declare(strict_types=1);

namespace Guard\Config\Project;

use Guard\Diagnostic\PolicyException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Locates the rule presets shipped with guard-php.
 */
final class PresetCatalog
{
    /**
     * Returns the preset names a project can import.
     *
     * @return list<string>
     */
    public function names(): array
    {
        return array_merge(['metrics', 'structure', 'disable-doc'], array_keys($this->defaults()), array_keys($this->documents()));
    }

    /**
     * Returns the document each Markdown preset declares.
     *
     * @return array<string, string>
     */
    public function documents(): array
    {
        return [
            'agents-md' => 'AGENTS.md',
            'claude-md' => 'CLAUDE.md',
            'readme-md' => 'README.md',
        ];
    }

    /**
     * Returns the default configuration file for each tool preset.
     *
     * @return array<string, string>
     */
    public function defaults(): array
    {
        return [
            'phpstan' => 'phpstan.neon',
            'phpstan-guard-rules' => 'phpstan.neon',
            'phpunit9' => 'phpunit9.xml.dist',
            'phpunit10' => 'phpunit10.xml.dist',
            'phpunit11' => 'phpunit11.xml.dist',
            'phpunit12' => 'phpunit12.xml.dist',
            'phpunit13' => 'phpunit.xml.dist',
            'doctest9' => 'phpunit9.xml.dist',
            'doctest10' => 'phpunit10.xml.dist',
            'doctest11' => 'phpunit11.xml.dist',
            'doctest12' => 'phpunit12.xml.dist',
            'doctest13' => 'phpunit.xml.dist',
            'php-cs-fixer' => '.php-cs-fixer.dist.php',
            'phpcs' => 'phpcs.xml.dist',
            'deptrac' => 'deptrac.yaml',
            'infection' => 'infection.json5',
            'composer' => 'composer.json',
            'github-actions' => '.github/workflows/ci.yml',
            'mutation' => '.github/workflows/mutation.yml',
            'pbt' => 'composer.json',
            'fuzz' => 'composer.json',
            'phpbench' => 'phpbench.json.dist',
            'docgen' => 'composer.json',
        ];
    }

    /**
     * Returns the filenames that satisfy one preset, preferred filename first.
     *
     * @return list<string>
     */
    public function candidates(string $preset): array
    {
        $phpunit = $this->phpunitCandidates($preset);
        if ($phpunit !== []) {
            return $phpunit;
        }
        $default = $this->defaults()[$preset] ?? null;
        if (!is_string($default)) {
            return [];
        }

        return match ($preset) {
            'phpstan', 'phpstan-guard-rules' => ['phpstan.neon', 'phpstan.neon.dist'],
            'php-cs-fixer' => ['.php-cs-fixer.dist.php', '.php-cs-fixer.php'],
            'phpcs' => ['phpcs.xml.dist', 'phpcs.xml'],
            'deptrac' => ['deptrac.yaml', 'deptrac.yml'],
            'phpbench' => ['phpbench.json.dist', 'phpbench.json'],
            default => [$default],
        };
    }

    /**
     * Returns PHPUnit configuration filenames for a PHPUnit or doctest preset.
     *
     * @return list<string>
     */
    public function phpunitCandidates(string $preset): array
    {
        $files = [
            'phpunit9' => ['phpunit9.xml.dist', 'phpunit9.xml'],
            'phpunit10' => ['phpunit10.xml.dist', 'phpunit10.xml'],
            'phpunit11' => ['phpunit11.xml.dist', 'phpunit11.xml'],
            'phpunit12' => ['phpunit12.xml.dist', 'phpunit12.xml'],
            'phpunit13' => ['phpunit.xml.dist', 'phpunit.xml'],
        ];
        $alias = [
            'doctest9' => 'phpunit9',
            'doctest10' => 'phpunit10',
            'doctest11' => 'phpunit11',
            'doctest12' => 'phpunit12',
            'doctest13' => 'phpunit13',
        ];
        $key = $alias[$preset] ?? $preset;
        if (!isset($files[$key])) {
            return [];
        }
        if ($key === 'phpunit13') {
            return $files[$key];
        }

        return array_merge($files[$key], ['phpunit.xml.dist', 'phpunit.xml']);
    }

    /**
     * Returns the package directory that contains rules/.
     */
    public function directory(): string
    {
        return dirname(__DIR__, 3) . '/rules';
    }

    /**
     * Returns the absolute path of one shipped preset.
     *
     * @throws PolicyException when the name is not a shipped preset
     */
    public function file(string $name): string
    {
        $this->assertKnown($name);

        return $this->directory() . '/' . $name . '.yaml';
    }

    /**
     * Returns import paths relative to a project for the selected presets.
     *
     * @param list<string> $names
     * @return list<string>
     * @throws PolicyException when a name is not a shipped preset
     */
    public function imports(string $root, array $names): array
    {
        $paths = [];
        foreach ($names as $name) {
            $paths[] = $this->importPath($root, $name);
        }

        return $paths;
    }

    /**
     * Returns one import path relative to the project root.
     *
     * A project that installed the package uses its vendor path. The package itself uses rules/.
     *
     * @throws PolicyException when the name is not a shipped preset
     */
    public function importPath(string $root, string $name): string
    {
        $this->assertKnown($name);
        $vendor = 'vendor/k-kinzal/guard-php/rules/' . $name . '.yaml';
        if (is_file($root . '/' . $vendor)) {
            return $vendor;
        }

        return $this->relative($root, $this->directory() . '/' . $name . '.yaml');
    }

    /**
     * Returns a relative path from a project directory to a preset file.
     */
    public function relative(string $root, string $target): string
    {
        $from = $this->parts($this->absolute($root));
        $to = $this->parts($this->absolute($target));
        while ($from !== [] && $to !== [] && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }
        $prefix = $from === [] ? '' : str_repeat('../', count($from));

        return $prefix . implode('/', $to);
    }

    /**
     * Returns a canonical path when the filesystem has one.
     */
    public function absolute(string $path): string
    {
        $real = realpath($path);

        return $real === false ? $path : $real;
    }

    /**
     * Splits a path into directory names.
     *
     * @return list<string>
     */
    public function parts(string $path): array
    {
        return explode('/', str_replace('\\', '/', rtrim($path, '/')));
    }

    /**
     * Reads one shipped preset.
     *
     * @return array<string, mixed>
     * @throws PolicyException when the preset is missing or not a mapping
     */
    public function read(string $name): array
    {
        $path = $this->file($name);
        if (!is_file($path)) {
            throw new PolicyException('Guard preset "' . $name . '" was not found at ' . $path . '. Install k-kinzal/guard-php so its rules/ directory is present.');
        }
        try {
            $parsed = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new PolicyException('Invalid guard preset ' . $name . ': ' . $exception->getMessage() . '. Correct rules/' . $name . '.yaml.', 0, $exception);
        }
        if (!is_array($parsed) || ($parsed !== [] && array_values($parsed) === $parsed)) {
            throw new PolicyException('Guard preset "' . $name . '" must be a mapping. Correct rules/' . $name . '.yaml.');
        }
        $document = [];
        foreach ($parsed as $key => $value) {
            if (is_string($key)) {
                $document[$key] = $value;
            }
        }

        return $document;
    }

    /**
     * Returns the configuration rule ids declared by one preset.
     *
     * @return list<string>
     * @throws PolicyException when the preset has no configuration rule ids
     */
    public function ruleIds(string $name): array
    {
        $configuration = $this->read($name)['configuration'] ?? null;
        if (!is_array($configuration) || array_values($configuration) !== $configuration) {
            throw new PolicyException('rules/' . $name . '.yaml configuration must be a list of field rules.');
        }
        $ids = [];
        foreach ($configuration as $rule) {
            if (!is_array($rule) || !is_string($rule['id'] ?? null) || $rule['id'] === '') {
                throw new PolicyException('rules/' . $name . '.yaml configuration rules must have a non-empty id.');
            }
            $ids[] = $rule['id'];
        }

        return $ids;
    }

    /**
     * Returns the standard metric limits shipped in metrics.yaml.
     *
     * @return array<string, array<string, int>>
     * @throws PolicyException when metrics.yaml does not define integer limits
     */
    public function limits(): array
    {
        $metrics = $this->read('metrics')['metrics'] ?? null;
        $profiles = is_array($metrics) ? ($metrics['profiles'] ?? null) : null;
        $standard = is_array($profiles) ? ($profiles['standard'] ?? null) : null;
        $limits = is_array($standard) ? ($standard['limits'] ?? null) : null;
        if (!is_array($limits) || !$this->isMapping($limits)) {
            throw new PolicyException('rules/metrics.yaml must define metrics.profiles.standard.limits.');
        }

        return $this->metrics($limits);
    }

    /**
     * Checks that every limit kind maps to integer metrics.
     *
     * @param array<mixed> $limits
     * @return array<string, array<string, int>>
     * @throws PolicyException when a limit is not an integer
     */
    public function metrics(array $limits): array
    {
        $result = [];
        foreach ($limits as $kind => $metrics) {
            if (!is_string($kind) || !is_array($metrics) || !$this->isMapping($metrics)) {
                throw new PolicyException('rules/metrics.yaml limits must map each kind to numeric metrics.');
            }
            $values = [];
            foreach ($metrics as $metric => $value) {
                if (!is_string($metric) || !is_int($value)) {
                    $label = is_string($metric) ? $kind . '.' . $metric : $kind;
                    throw new PolicyException('rules/metrics.yaml limit "' . $label . '" must be an integer.');
                }
                $values[$metric] = $value;
            }
            $result[$kind] = $values;
        }

        return $result;
    }

    /**
     * Rejects a preset name that is not shipped.
     *
     * @throws PolicyException when the name is unknown
     */
    public function assertKnown(string $name): void
    {
        if (!in_array($name, $this->names(), true)) {
            throw new PolicyException('Unknown guard import "' . $name . '". Use: ' . implode(', ', $this->names()) . '.');
        }
    }

    /**
     * Reports whether the value is a YAML mapping.
     *
     * @param mixed $value
     */
    public function isMapping($value): bool
    {
        return is_array($value) && ($value === [] || array_values($value) !== $value);
    }
}

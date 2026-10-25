<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ClassPair;

/**
 * PSR-4 namespace-to-directory entries declared in a project's composer.json.
 */
final class ComposerAutoloadMap
{
    /**
     * @var list<array{string, string}>
     * @readonly
     */
    private array $entries;

    /**
     * @param list<array{string, string}> $entries namespace prefix without trailing separator and root-relative directory
     */
    public function __construct(array $entries)
    {
        $normalized = [];
        foreach ($entries as [$namespace, $directory]) {
            $normalized[] = [trim($namespace, '\\'), self::normalizePath($directory)];
        }

        $this->entries = $normalized;
    }

    /**
     * Reads the autoload and autoload-dev PSR-4 sections of composer.json under the project root.
     */
    public static function fromProjectRoot(string $projectRoot): self
    {
        $file = rtrim(str_replace('\\', '/', $projectRoot), '/') . '/composer.json';
        if (!is_file($file)) {
            return new self([]);
        }

        $json = file_get_contents($file);
        $decoded = $json === false ? null : json_decode($json, true);
        if (!is_array($decoded)) {
            return new self([]);
        }

        $entries = [];
        foreach (['autoload', 'autoload-dev'] as $section) {
            $block = $decoded[$section] ?? null;
            $psr4 = is_array($block) ? $block['psr-4'] ?? null : null;
            if (!is_array($psr4)) {
                continue;
            }

            foreach ($psr4 as $namespace => $directories) {
                foreach (is_array($directories) ? $directories : [$directories] as $directory) {
                    if (is_string($directory)) {
                        $entries[] = [(string) $namespace, $directory];
                    }
                }
            }
        }

        return new self($entries);
    }

    /**
     * Converts a directory value to slash-separated form without a leading "./" or surrounding slashes.
     */
    public static function normalizePath(string $path): string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($normalized, './')) {
            $normalized = substr($normalized, 2);
        }

        return $normalized === '.' ? '' : $normalized;
    }

    /**
     * Returns the namespace that PSR-4 assigns to a root-relative directory, or null when no entry covers it.
     */
    public function namespaceForPath(string $path): ?string
    {
        $target = self::normalizePath($path);
        $namespace = null;
        $matched = -1;
        foreach ($this->entries as [$prefix, $directory]) {
            $inside = $directory === '' || $target === $directory || str_starts_with($target, $directory . '/');
            if (!$inside || strlen($directory) <= $matched) {
                continue;
            }

            $remainder = $directory === '' ? $target : substr($target, strlen($directory) + 1);
            $namespace = trim($prefix . '\\' . str_replace('/', '\\', $remainder), '\\');
            $matched = strlen($directory);
        }

        return $namespace;
    }

    /**
     * Returns the root-relative PHP file PSR-4 expects for a class, or null when no entry covers its namespace.
     */
    public function fileForClass(string $class): ?string
    {
        $file = null;
        $matched = -1;
        foreach ($this->entries as [$namespace, $directory]) {
            $prefix = $namespace === '' ? '' : $namespace . '\\';
            if (!str_starts_with($class, $prefix) || strlen($prefix) <= $matched) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = ($directory === '' ? '' : $directory . '/') . $relative . '.php';
            $matched = strlen($prefix);
        }

        return $file;
    }
}

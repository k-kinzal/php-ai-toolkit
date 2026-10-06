<?php

declare(strict_types=1);

namespace Toolkit\Guard\Config;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Toolkit\Guard\Policy\PolicyException;

/**
 * Loads a guard document and the documents it imports.
 *
 * Paths are relative to the file that lists them. Later imports override earlier ones, and the current file overrides its imports.
 */
final class ImportResolver
{
    /** @var list<string> */
    private array $seen = [];

    /** @var list<string> */
    private array $paths = [];

    /**
     * Loads one guard document, following its imports.
     *
     * @return array<string, mixed>
     * @throws PolicyException when the file is missing, circular, or not a version 1 document
     */
    public function resolve(string $path, bool $root = true): array
    {
        if (!is_file($path)) {
            $message = $root
                ? 'Guard configuration not found: ' . $path . '. Run guard init first.'
                : 'Guard import not found: ' . $path . '. Check that the path is relative to the file that lists it.';
            throw new PolicyException($message);
        }
        $identity = realpath($path);
        $identity = $identity === false ? $path : $identity;
        if (in_array($identity, $this->seen, true)) {
            throw new PolicyException('Circular guard import: ' . implode(' -> ', array_merge($this->paths, [$path])) . '. Remove the repeated import.');
        }
        $this->seen[] = $identity;
        $this->paths[] = $path;
        try {
            return $this->document($path, $root);
        } finally {
            array_pop($this->seen);
            array_pop($this->paths);
        }
    }

    /**
     * Parses one document, loads its imports, and overlays the document on those imports.
     *
     * @return array<string, mixed>
     * @throws PolicyException when the document is malformed
     */
    public function document(string $path, bool $root): array
    {
        $data = $this->mapping($path, $root);
        $this->version($data, $path, $root);
        $this->assertUnique($data, $path);
        $merged = [];
        foreach ($this->imports($data['imports'] ?? null, $path, $root) as $import) {
            $merged = (new DocumentMerger())->merge($merged, $this->resolve($import, false));
        }
        unset($data['imports']);

        return (new DocumentMerger())->merge($merged, $data);
    }

    /**
     * Reads a YAML mapping and rejects unknown top-level keys.
     *
     * @return array<string, mixed>
     * @throws PolicyException when the YAML cannot be parsed or contains unknown keys
     */
    public function mapping(string $path, bool $root): array
    {
        try {
            $parsed = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            $name = $root ? 'guard.yaml' : basename($path);
            throw new PolicyException('Invalid ' . $name . ': ' . $exception->getMessage() . '. Correct the YAML syntax.', 0, $exception);
        }
        $context = $root ? 'guard.yaml' : basename($path);

        return (new Schema())->mapping($parsed, ['version', 'imports', 'metrics', 'structure', 'documentation', 'configuration'], $context);
    }

    /**
     * Requires version 1 on the project file and on any imported file that sets version.
     *
     * @param array<string, mixed> $data
     * @throws PolicyException when version is missing on the project file or is not 1
     */
    public function version(array $data, string $path, bool $root): void
    {
        if (!array_key_exists('version', $data)) {
            if ($root) {
                throw new PolicyException('guard.yaml.version must be 1. Use guard init to generate the current schema.');
            }

            return;
        }
        if ($data['version'] !== 1) {
            $name = $root ? 'guard.yaml' : basename($path);
            throw new PolicyException($name . '.version must be 1. Use guard init to generate the current schema.');
        }
    }

    /**
     * Rejects two configuration rules with the same id in one file.
     *
     * @param array<string, mixed> $data
     * @throws PolicyException when a file repeats a configuration rule id
     */
    public function assertUnique(array $data, string $path): void
    {
        $rules = $data['configuration'] ?? null;
        if (!is_array($rules) || array_values($rules) !== $rules) {
            return;
        }
        $seen = [];
        foreach ($rules as $rule) {
            if (!is_array($rule) || !is_string($rule['id'] ?? null) || $rule['id'] === '') {
                continue;
            }
            if (isset($seen[$rule['id']])) {
                throw new PolicyException('Duplicate configuration rule "' . $rule['id'] . '" in ' . $path . '. Give each rule a unique id in that file.');
            }
            $seen[$rule['id']] = true;
        }
    }

    /**
     * Resolves import paths from the directory of the file that lists them.
     *
     * @param mixed $value
     * @return list<string>
     * @throws PolicyException when imports is not a list of paths
     */
    public function imports($value, string $path, bool $root): array
    {
        if ($value === null) {
            return [];
        }
        $context = ($root ? 'guard.yaml' : basename($path)) . '.imports';
        $directory = dirname($path);
        $paths = [];
        foreach ((new Schema())->strings($value, $context) as $import) {
            $paths[] = str_starts_with($import, '/') ? $import : $directory . '/' . $import;
        }

        return $paths;
    }
}

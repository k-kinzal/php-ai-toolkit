<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Discovery\Package;

use function array_filter;
use function basename;
use function dirname;
use function file_get_contents;
use function is_array;
use function is_file;
use function json_decode;
use function json_last_error_msg;
use function rtrim;
use function sprintf;
use function str_replace;

use Toolkit\DocGen\DocGenException;

/**
 * Reads composer.json files into ComposerManifest values.
 *
 * @visibility Toolkit\DocGen\Discovery
 */
final class ComposerManifestReader
{
    /** @readonly */
    private RepositoryAddress $repositoryUrl;

    /**
     * Creates a manifest reader.
     */
    public function __construct(?RepositoryAddress $repositoryUrl = null)
    {
        $this->repositoryUrl = $repositoryUrl ?? new RepositoryAddress();
    }

    /**
     * Reads and validates one composer.json file.
     *
     * @throws DocGenException when the file is missing or not valid JSON
     */
    public function read(string $path): ComposerManifest
    {
        $data = json_decode($this->contents($path), true);
        if (!is_array($data)) {
            throw new DocGenException(sprintf('Invalid composer.json at %s: %s', $path, json_last_error_msg()));
        }
        $sections = array_filter($data, 'is_array');
        $strings = array_filter($data, 'is_string');
        $autoload = [];
        $classmap = [];
        foreach (['autoload', 'autoload-dev'] as $key) {
            $section = array_filter($sections[$key] ?? [], 'is_array');
            $autoload[$key] = [];
            foreach ($section['psr-4'] ?? [] as $prefix => $paths) {
                $directories = $this->paths(array_filter(is_array($paths) ? $paths : [$paths], 'is_string'), true);
                if ($directories !== []) {
                    $autoload[$key][(string) $prefix] = $directories;
                }
            }
            $classmap[$key] = $this->paths(array_filter($section['classmap'] ?? [], 'is_string'), false);
        }
        $constraints = [];
        foreach (['require', 'require-dev', 'suggest'] as $key) {
            $constraints[$key] = [];
            foreach (array_filter($sections[$key] ?? [], 'is_string') as $name => $constraint) {
                $constraints[$key][(string) $name] = $constraint;
            }
        }
        $directory = dirname($path);
        $name = $strings['name'] ?? '';
        $support = $sections['support'] ?? [];

        return new ComposerManifest(
            $directory,
            $name !== '' ? $name : basename($directory),
            $strings['description'] ?? '',
            $autoload['autoload'],
            $autoload['autoload-dev'],
            $constraints['require'],
            $constraints['require-dev'],
            $constraints['suggest'],
            $classmap['autoload'],
            $classmap['autoload-dev'],
            $this->repositoryUrl->read($support['source'] ?? null) ?? $this->repositoryUrl->read($data['homepage'] ?? null) ?? '',
        );
    }

    /**
     * Reads the contents of one manifest before JSON validation.
     *
     * @throws DocGenException when the file is missing or unreadable
     */
    public function contents(string $path): string
    {
        if (!is_file($path)) {
            throw new DocGenException(sprintf('Composer manifest not found: %s', $path));
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new DocGenException(sprintf('Composer manifest is not readable: %s', $path));
        }

        return $contents;
    }

    /**
     * Normalizes validated paths, retaining empty paths only for PSR-4 package roots.
     *
     * @param array<array-key, string> $entries
     * @return list<string>
     */
    public function paths(array $entries, bool $keepEmpty): array
    {
        $paths = [];
        foreach ($entries as $entry) {
            $path = rtrim(str_replace('\\', '/', $entry), '/');
            if ($path !== '' || $keepEmpty) {
                $paths[] = $path;
            }
        }

        return $paths;
    }
}

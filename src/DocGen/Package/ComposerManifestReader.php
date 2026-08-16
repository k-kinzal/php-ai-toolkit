<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Package;

use function basename;
use function dirname;
use function file_get_contents;
use function is_array;
use function is_file;
use function is_string;
use function json_decode;
use function json_last_error_msg;

use PhpAiToolkit\DocGen\Config\RepositoryUrl;
use PhpAiToolkit\DocGen\DocGenException;

use function rtrim;
use function sprintf;
use function str_replace;

/**
 * Reads composer.json files into ComposerManifest values.
 */
final class ComposerManifestReader
{
    /** @readonly */
    private RepositoryUrl $repositoryUrl;

    /**
     * Creates a manifest reader.
     */
    public function __construct(?RepositoryUrl $repositoryUrl = null)
    {
        $this->repositoryUrl = $repositoryUrl ?? new RepositoryUrl();
    }

    /**
     * Reads and validates one composer.json file.
     *
     * @throws DocGenException when the file is missing or not valid JSON
     */
    public function read(string $path): ComposerManifest
    {
        if (!is_file($path)) {
            throw new DocGenException(sprintf('Composer manifest not found: %s', $path));
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new DocGenException(sprintf('Composer manifest is not readable: %s', $path));
        }

        $data = json_decode($contents, true);
        if (!is_array($data)) {
            throw new DocGenException(sprintf('Invalid composer.json at %s: %s', $path, json_last_error_msg()));
        }

        $directory = dirname($path);
        $name = isset($data['name']) && is_string($data['name']) && $data['name'] !== ''
            ? $data['name']
            : basename($directory);

        return new ComposerManifest(
            $directory,
            $name,
            isset($data['description']) && is_string($data['description']) ? $data['description'] : '',
            $this->psr4Map($data['autoload'] ?? null),
            $this->psr4Map($data['autoload-dev'] ?? null),
            $this->constraintMap($data['require'] ?? null),
            $this->constraintMap($data['require-dev'] ?? null),
            $this->constraintMap($data['suggest'] ?? null),
            $this->classmapList($data['autoload'] ?? null),
            $this->classmapList($data['autoload-dev'] ?? null),
            $this->repository($data),
        );
    }

    /**
     * Reads where one manifest says its sources can be browsed.
     *
     * A package states that under "support.source", which is what composer
     * itself links a package to its code with. Where a package states no
     * source, its "homepage" is the only address it offers a reader, so it
     * stands in for one; a package that states neither is documented
     * without a link to anywhere.
     */
    public function repository(mixed $data): string
    {
        if (!is_array($data)) {
            return '';
        }

        $support = is_array($data['support'] ?? null) ? $data['support'] : [];

        return $this->repositoryUrl->read($support['source'] ?? null)
            ?? $this->repositoryUrl->read($data['homepage'] ?? null)
            ?? '';
    }

    /**
     * Normalizes a composer autoload section into a PSR-4 prefix map.
     *
     * An empty path stays an empty string because composer maps a prefix to
     * the package root that way, as many Symfony components do.
     *
     * @return array<string, list<string>>
     */
    public function psr4Map(mixed $autoload): array
    {
        if (!is_array($autoload) || !isset($autoload['psr-4']) || !is_array($autoload['psr-4'])) {
            return [];
        }

        $map = [];
        foreach ($autoload['psr-4'] as $prefix => $paths) {
            $directories = [];
            foreach (is_array($paths) ? $paths : [$paths] as $entry) {
                if (is_string($entry)) {
                    $directories[] = rtrim(str_replace('\\', '/', $entry), '/');
                }
            }

            if ($directories !== []) {
                $map[(string) $prefix] = $directories;
            }
        }

        return $map;
    }

    /**
     * Normalizes a composer autoload section into a classmap path list.
     *
     * Composer allows directories and single files in a classmap, so the
     * entries stay relative paths and are not resolved here.
     *
     * @return list<string>
     */
    public function classmapList(mixed $autoload): array
    {
        if (!is_array($autoload) || !isset($autoload['classmap']) || !is_array($autoload['classmap'])) {
            return [];
        }

        $paths = [];
        foreach ($autoload['classmap'] as $entry) {
            $path = is_string($entry) ? rtrim(str_replace('\\', '/', $entry), '/') : '';
            if ($path !== '') {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * Normalizes a composer dependency section into a name-to-constraint map.
     *
     * @return array<string, string>
     */
    public function constraintMap(mixed $section): array
    {
        if (!is_array($section)) {
            return [];
        }

        $map = [];
        foreach ($section as $name => $constraint) {
            if (is_string($constraint)) {
                $map[(string) $name] = $constraint;
            }
        }

        return $map;
    }
}

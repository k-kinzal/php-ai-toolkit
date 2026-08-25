<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render;

use function array_keys;
use function file_get_contents;
use function is_file;
use function ksort;

use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Package\DiscoveredPackage;

/**
 * Answers which pages a site has and what they are written from.
 *
 * The listings are derived from the model alone, so the set of pages a run
 * writes is decided before anything is rendered.
 */
final class SitePages
{
    /**
     * Lists the namespaces of one package in sorted order.
     *
     * @return list<string>
     */
    public function namespacesOf(ProjectModel $model, string $packageName): array
    {
        $namespaces = [];
        foreach ($model->classLikes as $classLike) {
            if ($classLike->packageName === $packageName && !$classLike->isDev) {
                $namespaces[$classLike->namespace] = true;
            }
        }

        foreach ($model->functions as $function) {
            if ($function->packageName === $packageName && !$function->isDev) {
                $namespaces[$function->namespace] = true;
            }
        }

        ksort($namespaces);

        return array_keys($namespaces);
    }

    /**
     * Lists every unique source file of the model, tests included.
     *
     * @return list<string>
     */
    public function sourceFiles(ProjectModel $model): array
    {
        $files = [];
        foreach ($model->classLikes as $classLike) {
            $files[$classLike->file] = true;
        }

        foreach ($model->functions as $function) {
            $files[$function->file] = true;
        }

        ksort($files);

        return array_keys($files);
    }

    /**
     * Reads the README of a package when one exists.
     */
    public function readme(DiscoveredPackage $package): ?string
    {
        return $this->contents($package->manifest->directory . '/README.md');
    }

    /**
     * Reads one file, or returns null when it cannot be read.
     */
    public function contents(string $path): ?string
    {
        $contents = is_file($path) ? file_get_contents($path) : false;

        return $contents === false ? null : $contents;
    }
}

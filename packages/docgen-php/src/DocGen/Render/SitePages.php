<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render;

use function array_keys;
use function file_get_contents;
use function is_file;
use function ksort;
use function strrpos;
use function substr;

use Toolkit\DocGen\Model\Package\DiscoveredPackage;
use Toolkit\DocGen\Model\ProjectModel;

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
            if ($classLike->packageName === $packageName && !$classLike->isDev
                && (!$model->publicApi || $model->isPublicApiClassLike($classLike->fqcn))) {
                $namespaces[$classLike->namespace] = true;
            }
        }

        foreach ($model->functions as $function) {
            if ($function->packageName === $packageName && !$function->isDev
                && (!$model->publicApi || $model->isPublicApiFunction($function->fqn))) {
                $namespaces[$function->namespace] = true;
            }
        }

        foreach (array_keys($namespaces) as $namespace) {
            while (($separator = strrpos($namespace, '\\')) !== false) {
                $namespace = substr($namespace, 0, $separator);
                $namespaces[$namespace] = true;
            }
        }

        ksort($namespaces);

        return array_keys($namespaces);
    }

    /**
     * Lists unique source files, limited to public API declarations in that mode.
     *
     * Complete sites also include test sources for coverage and call-site links.
     *
     * @return list<string>
     */
    public function sourceFiles(ProjectModel $model): array
    {
        $files = [];
        foreach ($model->classLikes as $classLike) {
            if (!$model->publicApi || (!$classLike->isDev && $model->isPublicApiClassLike($classLike->fqcn))) {
                $files[$classLike->file] = true;
            }
        }

        foreach ($model->functions as $function) {
            if (!$model->publicApi || (!$function->isDev && $model->isPublicApiFunction($function->fqn))) {
                $files[$function->file] = true;
            }
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

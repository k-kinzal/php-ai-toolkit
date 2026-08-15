<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render;

use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;

use function preg_replace;
use function str_repeat;
use function str_replace;
use function substr_count;

/**
 * Computes the output paths and relative links of the generated site.
 *
 * All pages link relatively, so the site works from a local directory and
 * from any GitHub Pages base path without configuration.
 */
final class SiteUrl
{
    /**
     * Returns the directory slug of a package name.
     */
    public function slug(string $packageName): string
    {
        return preg_replace('#[^A-Za-z0-9_./-]#', '-', $packageName) ?? $packageName;
    }

    /**
     * Returns the site path of a package index page.
     */
    public function packagePage(string $packageName): string
    {
        return $this->slug($packageName) . '/index.html';
    }

    /**
     * Returns the site path of a package's complete item listing.
     */
    public function allItemsPage(string $packageName): string
    {
        return $this->slug($packageName) . '/all-items.html';
    }

    /**
     * Returns the site path of one architecture layer listing.
     */
    public function layerPage(string $packageName, string $layerName): string
    {
        return $this->slug($packageName) . '/layer.' . $this->slug($layerName) . '.html';
    }

    /**
     * Returns the site path of a namespace index page.
     */
    public function namespacePage(string $packageName, string $namespace): string
    {
        if ($namespace === '') {
            return $this->packagePage($packageName);
        }

        return $this->slug($packageName) . '/' . str_replace('\\', '/', $namespace) . '/index.html';
    }

    /**
     * Returns the site path of a class-like symbol page.
     */
    public function classLikePage(ClassLikeDoc $classLike): string
    {
        $directory = $this->slug($classLike->packageName);
        if ($classLike->namespace !== '') {
            $directory .= '/' . str_replace('\\', '/', $classLike->namespace);
        }

        return $directory . '/' . $classLike->kind . '.' . $classLike->shortName . '.html';
    }

    /**
     * Returns the site path of a function page.
     */
    public function functionPage(FunctionDoc $function): string
    {
        $directory = $this->slug($function->packageName);
        if ($function->namespace !== '') {
            $directory .= '/' . str_replace('\\', '/', $function->namespace);
        }

        return $directory . '/function.' . $function->shortName . '.html';
    }

    /**
     * Returns the site path of a highlighted source page.
     */
    public function sourcePage(string $relativeFile): string
    {
        return 'src/' . $relativeFile . '.html';
    }

    /**
     * Returns the relative prefix that leads from a page to the site root.
     */
    public function prefix(string $fromPath): string
    {
        return str_repeat('../', substr_count($fromPath, '/'));
    }

    /**
     * Returns the relative href from one site path to another.
     */
    public function href(string $fromPath, string $toPath): string
    {
        return $this->prefix($fromPath) . $toPath;
    }
}

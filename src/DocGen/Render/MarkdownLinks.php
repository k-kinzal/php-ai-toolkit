<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render;

use function array_pop;
use function explode;
use function implode;
use function in_array;
use function preg_match;
use function strpos;
use function substr;

/**
 * Resolves Markdown links that point at documents of the same package.
 *
 * A repository keeps its prose in Markdown files that link to each other by
 * relative path. Those paths mean nothing in the generated site, so they are
 * resolved here against the documents that were rendered, turning a written
 * cross-reference into a working link. Targets outside that set stay
 * unresolved and are rendered as plain text by the caller.
 */
final class MarkdownLinks
{
    /** @readonly */
    private SiteUrl $url;

    /** @readonly */
    private string $packageName;

    /** @readonly */
    private string $pagePath;

    /** @readonly */
    private string $directory;

    /**
     * Document paths of the package, relative to the package directory.
     *
     * @var list<string>
     * @readonly
     */
    private array $paths;

    /**
     * Creates a link resolver for one rendered document.
     *
     * @param string $directory the directory of the rendered document, relative to the package
     * @param list<string> $paths the document paths of the package
     */
    public function __construct(SiteUrl $url, string $packageName, string $pagePath, string $directory, array $paths)
    {
        $this->url = $url;
        $this->packageName = $packageName;
        $this->pagePath = $pagePath;
        $this->directory = $directory;
        $this->paths = $paths;
    }

    /**
     * Resolves one link target to a site href, or returns null.
     */
    public function resolve(string $target): ?string
    {
        if (preg_match('~^([a-zA-Z][a-zA-Z0-9+.-]*:|//|#)~', $target) === 1) {
            return null;
        }

        $fragment = '';
        $position = strpos($target, '#');
        if ($position !== false) {
            $fragment = substr($target, $position);
            $target = substr($target, 0, $position);
        }

        if ($target === '') {
            return null;
        }

        $path = $this->normalize($this->directory === '' ? $target : $this->directory . '/' . $target);
        if (!in_array($path, $this->paths, true)) {
            return null;
        }

        return $this->url->href($this->pagePath, $this->url->documentPage($this->packageName, $path)) . $fragment;
    }

    /**
     * Normalizes a relative path by resolving its dot segments.
     */
    public function normalize(string $path): string
    {
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }
}

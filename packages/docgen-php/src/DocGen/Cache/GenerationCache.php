<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Cache;

use function sprintf;

/**
 * The caches one generation run reads from and writes back.
 *
 * The two halves of a run are cached separately because they are reused
 * separately: sources parse the same as long as they are unchanged, while
 * a page is written again as soon as anything it shows has changed. A run
 * without a cache holds neither half and behaves as it always did.
 *
 * @property-read ?ParseCache $sources
 * @property-read ?RenderCache $pages
 */
final class GenerationCache
{
    /**
     * Creates the caches of one run, or the absence of them.
     */
    public function __construct(
        /** @readonly */
        private ?ParseCache $sources = null,
        /** @readonly */
        private ?RenderCache $pages = null,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'sources' => $this->sources,
            'pages' => $this->pages,
            default => null,
        };
    }

    /**
     * Reads back what a run needs before anything of it is forked.
     *
     * Only the pages are read here: which page a run may leave alone is
     * one question about the whole site, while what a source parsed into
     * is one question per file, asked by the worker that owns the file.
     */
    public function load(): void
    {
        $this->pages?->load();
    }

    /**
     * Closes both caches once the run has written its site.
     *
     * The parse cache is filled by the workers as they read, and the page
     * cache by the render phase as it writes, so all that is left is to
     * drop what no run has read for a long time.
     */
    public function save(): void
    {
        $this->sources?->prune();
    }

    /**
     * Describes what this run took from the cache, or nothing.
     */
    public function summary(): ?string
    {
        if ($this->sources === null && $this->pages === null) {
            return null;
        }

        $sources = $this->sources;
        $pages = $this->pages;

        return sprintf(
            'Cache: %d of %d sources and %d of %d pages reused',
            $sources === null ? 0 : $sources->reused(),
            $sources === null ? 0 : $sources->reused() + $sources->parsed(),
            $pages === null ? 0 : $pages->reused(),
            $pages === null ? 0 : $pages->reused() + $pages->rendered(),
        );
    }
}

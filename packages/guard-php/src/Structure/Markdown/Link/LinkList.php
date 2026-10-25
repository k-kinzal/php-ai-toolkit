<?php

declare(strict_types=1);

namespace Guard\Structure\Markdown\Link;

use Guard\Structure\LocalReferences;

/**
 * Parsed local links and images, with their original destinations for diagnostics.
 */
final class LinkList implements LocalReferences
{
    /**
     * @param list<array{url: string, path: string, line: int}> $links
     */
    public function __construct(private array $links)
    {
    }

    /**
     * @return list<array{url: string, path: string, line: int}>
     */
    public function all(): array
    {
        return $this->links;
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        return array_values(array_unique(array_column($this->links, 'path')));
    }
}

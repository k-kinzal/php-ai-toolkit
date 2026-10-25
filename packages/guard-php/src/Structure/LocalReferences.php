<?php

declare(strict_types=1);

namespace Guard\Structure;

/**
 * Declares file-relative targets whose metadata the collector should resolve.
 */
interface LocalReferences extends Subject
{
    /**
     * @return list<string> relative filesystem paths, without URL query or fragment
     */
    public function paths(): array;
}

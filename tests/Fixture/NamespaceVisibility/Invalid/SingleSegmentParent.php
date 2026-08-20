<?php

declare(strict_types=1);

namespace NamespaceVisibilitySingleSegment;

/**
 * @visibility parent
 */
final class SingleSegmentParent
{
    public function run(): int
    {
        return 1;
    }
}

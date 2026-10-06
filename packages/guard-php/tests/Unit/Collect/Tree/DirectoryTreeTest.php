<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Tree;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Tree\DirectoryTree
 * @uses \Guard\Collect\Tree\Filesystem\DirectoryListing
 */
#[CoversClass(\Guard\Collect\Tree\DirectoryTree::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Tree\Filesystem\DirectoryListing::class)]
final class DirectoryTreeTest extends TestCase
{
    public function testKeepsRelativePathsForDepthAndDescendantPolicies(): void
    {
        $listing = new \Guard\Collect\Tree\Filesystem\DirectoryListing('src/a', ['One.php'], ['b']);
        $tree = new \Guard\Collect\Tree\DirectoryTree(['src/a' => $listing]);
        self::assertSame($listing, $tree->listings['src/a']);
        self::assertSame('src/a', $tree->listings['src/a']->relativePath);
    }

}

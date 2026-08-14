<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Filesystem;

use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DirectoryListing::class)]
final class DirectoryListingTest extends TestCase
{
    public function testStoresListingData(): void
    {
        $listing = new DirectoryListing('src/A', ['One.php', 'Two.php'], ['Sub']);

        self::assertSame('src/A', $listing->relativePath);
        self::assertSame(['One.php', 'Two.php'], $listing->fileNames);
        self::assertSame(['Sub'], $listing->dirNames);
    }
}

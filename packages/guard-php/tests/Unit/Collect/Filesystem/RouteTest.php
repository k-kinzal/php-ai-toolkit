<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Filesystem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Filesystem\Route
 */
#[CoversClass(\Guard\Collect\Filesystem\Route::class)]
final class RouteTest extends TestCase
{
    public function testRetainsMatcherStateAndAncestorsWithoutFilesystemHandles(): void
    {
        $route = new \Guard\Collect\Filesystem\Route('input', ['**', '*.md'], [0, 1], ['/project']);
        self::assertSame([0, 1], $route->positions);
        self::assertSame(['/project'], $route->ancestors);
    }
}

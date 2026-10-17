<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Filesystem;

use Guard\Collect\Filesystem\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Filesystem\Route
 */
#[CoversClass(Route::class)]
final class RouteTest extends TestCase
{
    public function testRetainsMatcherStateAndAncestorsWithoutFilesystemHandles(): void
    {
        $route = new Route('input', ['**', '*.md'], [0, 1], ['/project']);
        self::assertSame([0, 1], $route->positions);
        self::assertSame(['/project'], $route->ancestors);
    }
}

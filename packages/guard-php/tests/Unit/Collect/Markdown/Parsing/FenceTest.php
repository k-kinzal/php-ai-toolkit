<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Markdown\Parsing;

use Guard\Collect\Markdown\Parsing\Fence;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Markdown\Parsing\Fence
 */
#[CoversClass(Fence::class)]
final class FenceTest extends TestCase
{
    public function testGetExposesMarkerAndLength(): void
    {
        $fence = new Fence('~', 4);

        self::assertSame('~', $fence->marker);
        self::assertSame(4, $fence->length);
    }
}

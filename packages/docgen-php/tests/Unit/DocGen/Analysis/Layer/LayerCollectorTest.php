<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Layer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Layer\LayerCollector;

/**
 * @covers \Toolkit\DocGen\Analysis\Layer\LayerCollector
 */
#[CoversClass(LayerCollector::class)]
final class LayerCollectorTest extends TestCase
{
    public function testStoresCollectorData(): void
    {
        $collector = new LayerCollector('directory', 'src/Domain/.*');

        self::assertSame('directory', $collector->type);
        self::assertSame('src/Domain/.*', $collector->value);
    }
}

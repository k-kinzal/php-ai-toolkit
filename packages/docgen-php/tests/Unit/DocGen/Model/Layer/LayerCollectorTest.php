<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Model\Layer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Model\Layer\LayerCollector;

/**
 * @covers \Toolkit\DocGen\Model\Layer\LayerCollector
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

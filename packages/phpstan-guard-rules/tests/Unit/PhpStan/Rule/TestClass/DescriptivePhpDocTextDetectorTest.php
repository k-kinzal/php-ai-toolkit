<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\TestClass\DescriptivePhpDocTextDetector;

/**
 * @covers \Toolkit\PhpStan\Rule\TestClass\DescriptivePhpDocTextDetector
 */
#[CoversClass(DescriptivePhpDocTextDetector::class)]
final class DescriptivePhpDocTextDetectorTest extends TestCase
{
    public function testHasDetectsDescriptionBeforeAnnotation(): void
    {
        self::assertTrue((new DescriptivePhpDocTextDetector())->has("/**\n * Description.\n * @dataProvider provider\n */"));
        self::assertFalse((new DescriptivePhpDocTextDetector())->has("/**\n * @dataProvider provider\n */"));
    }

    public function testCleanLineRemovesPhpDocDelimiters(): void
    {
        self::assertSame('Description.', (new DescriptivePhpDocTextDetector())->cleanLine(' * Description.'));
    }
}

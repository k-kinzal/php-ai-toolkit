<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use PhpAiToolkit\PhpStan\Rule\TestClass\DescriptivePhpDocTextDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\TestClass\DescriptivePhpDocTextDetector
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

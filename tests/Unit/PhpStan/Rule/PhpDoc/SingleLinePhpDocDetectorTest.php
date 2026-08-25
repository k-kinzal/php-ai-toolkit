<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PhpAiToolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocDetector
 */
#[CoversClass(SingleLinePhpDocDetector::class)]
final class SingleLinePhpDocDetectorTest extends TestCase
{
    public function testIsSingleLineDetectsSingleLinePhpDoc(): void
    {
        self::assertTrue((new SingleLinePhpDocDetector())->isSingleLine('/** doc */'));
        self::assertFalse((new SingleLinePhpDocDetector())->isSingleLine("/**\n * doc\n */"));
    }
}

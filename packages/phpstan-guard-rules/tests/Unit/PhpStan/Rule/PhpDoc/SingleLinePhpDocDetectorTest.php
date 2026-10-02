<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocDetector;

/**
 * @covers \Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocDetector
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

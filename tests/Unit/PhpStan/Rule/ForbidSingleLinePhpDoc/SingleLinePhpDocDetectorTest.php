<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ForbidSingleLinePhpDoc;

use PhpAiToolkit\PhpStan\Rule\ForbidSingleLinePhpDoc\SingleLinePhpDocDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SingleLinePhpDocDetector::class)]
final class SingleLinePhpDocDetectorTest extends TestCase
{
    public function testIsSingleLineDetectsSingleLinePhpDoc(): void
    {
        self::assertTrue((new SingleLinePhpDocDetector())->isSingleLine('/** doc */'));
        self::assertFalse((new SingleLinePhpDocDetector())->isSingleLine("/**\n * doc\n */"));
    }
}

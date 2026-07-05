<?php

declare(strict_types=1);

namespace Tests\Unit\Installer;

use PhpAiToolkit\Installer\PathNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathNormalizer::class)]
final class PathNormalizerTest extends TestCase
{
    public function testNormalizeConvertsBackslashesAndRemovesTrailingSlash(): void
    {
        self::assertSame('C:/project/src', (new PathNormalizer())->normalize('C:\\project\\src\\'));
    }
}

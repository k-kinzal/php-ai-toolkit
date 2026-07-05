<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\NamespacePrefixNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NamespacePrefixNormalizer::class)]
final class NamespacePrefixNormalizerTest extends TestCase
{
    public function testNormalizeConvertsSeparatorsAndTrimsNamespaceBoundaries(): void
    {
        self::assertSame('Tests\Support', (new NamespacePrefixNormalizer())->normalize('\\Tests/Support\\'));
    }
}

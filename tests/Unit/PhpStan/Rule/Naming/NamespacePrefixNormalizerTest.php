<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Naming;

use PhpAiToolkit\PhpStan\Rule\Naming\NamespacePrefixNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\Naming\NamespacePrefixNormalizer
 */
#[CoversClass(NamespacePrefixNormalizer::class)]
final class NamespacePrefixNormalizerTest extends TestCase
{
    public function testNormalizeConvertsSeparatorsAndTrimsNamespaceBoundaries(): void
    {
        self::assertSame('Tests\Support', (new NamespacePrefixNormalizer())->normalize('\\Tests/Support\\'));
    }
}

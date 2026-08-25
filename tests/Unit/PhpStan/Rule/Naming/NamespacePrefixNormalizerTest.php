<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Naming;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Naming\NamespacePrefixNormalizer;

/**
 * @covers \Toolkit\PhpStan\Rule\Naming\NamespacePrefixNormalizer
 */
#[CoversClass(NamespacePrefixNormalizer::class)]
final class NamespacePrefixNormalizerTest extends TestCase
{
    public function testNormalizeConvertsSeparatorsAndTrimsNamespaceBoundaries(): void
    {
        self::assertSame('Tests\Support', (new NamespacePrefixNormalizer())->normalize('\\Tests/Support\\'));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Naming;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Naming\ForbiddenNamespacePrefixes;
use Toolkit\PhpStan\Rule\Naming\NamespacePrefixNormalizer;

/**
 * @covers \Toolkit\PhpStan\Rule\Naming\ForbiddenNamespacePrefixes
 * @uses \Toolkit\PhpStan\Rule\Naming\NamespacePrefixNormalizer
 */
#[CoversClass(ForbiddenNamespacePrefixes::class)]
#[UsesClass(NamespacePrefixNormalizer::class)]
final class ForbiddenNamespacePrefixesTest extends TestCase
{
    public function testMatchingPrefixReturnsForbiddenPrefix(): void
    {
        $prefixes = new ForbiddenNamespacePrefixes(['Tests/Support']);

        self::assertSame('Tests\Support', $prefixes->matchingPrefix('Tests\Support\Fixture'));
        self::assertNull($prefixes->matchingPrefix('Tests\Domain'));
    }
}

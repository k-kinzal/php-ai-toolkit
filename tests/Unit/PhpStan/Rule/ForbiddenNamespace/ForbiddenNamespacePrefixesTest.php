<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ForbiddenNamespace;

use PhpAiToolkit\PhpStan\Rule\ForbiddenNamespace\ForbiddenNamespacePrefixes;
use PhpAiToolkit\PhpStan\Rule\ForbiddenNamespace\NamespacePrefixNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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

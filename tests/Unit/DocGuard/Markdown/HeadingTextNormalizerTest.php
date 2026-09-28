<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Markdown;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Markdown\HeadingTextNormalizer;

/**
 * @covers \Toolkit\DocGuard\Markdown\HeadingTextNormalizer
 */
#[CoversClass(HeadingTextNormalizer::class)]
final class HeadingTextNormalizerTest extends TestCase
{
    public function testNormalizeCollapsesSpacesAndTabs(): void
    {
        self::assertSame('Getting Started `now`', (new HeadingTextNormalizer())->normalize("  Getting \t  Started   `now`  "));
    }

    public function testNormalizeKeepsInlineMarkup(): void
    {
        self::assertSame('[Link](url) **bold**', (new HeadingTextNormalizer())->normalize('[Link](url) **bold**'));
    }
}

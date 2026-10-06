<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Markdown\Parsing;

use Guard\Collect\Markdown\Parsing\HeadingTextNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Markdown\Parsing\HeadingTextNormalizer
 * @uses \Guard\Collect\Markdown\Parsing\Heading
 */
#[CoversClass(HeadingTextNormalizer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Markdown\Parsing\Heading::class)]
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

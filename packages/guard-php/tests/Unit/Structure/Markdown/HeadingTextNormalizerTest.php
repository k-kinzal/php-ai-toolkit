<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Markdown;

use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingTextNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Markdown\HeadingTextNormalizer
 * @uses \Guard\Structure\Markdown\Heading
 */
#[CoversClass(HeadingTextNormalizer::class)]
#[UsesClass(Heading::class)]
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

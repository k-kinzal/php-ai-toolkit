<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PhpAiToolkit\DocGen\Render\MarkdownLinks;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Render\MarkdownLinks
 * @uses \PhpAiToolkit\DocGen\Render\SiteUrl
 */
#[CoversClass(MarkdownLinks::class)]
#[UsesClass(SiteUrl::class)]
final class MarkdownLinksTest extends TestCase
{
    public function testResolveLinksADocumentOfTheSamePackage(): void
    {
        $links = new MarkdownLinks(new SiteUrl(), 'demo/pkg', 'demo/pkg/index.html', '', ['README.md', 'docs/guide.md']);

        self::assertSame('../../demo/pkg/doc/docs/guide.md.html', $links->resolve('docs/guide.md'));
        self::assertSame('../../demo/pkg/doc/docs/guide.md.html#usage', $links->resolve('docs/guide.md#usage'));
    }

    public function testResolveWalksOutOfTheDocumentDirectory(): void
    {
        $links = new MarkdownLinks(new SiteUrl(), 'demo/pkg', 'demo/pkg/doc/docs/rules/Rule.md.html', 'docs/rules', ['docs/guide.md', 'docs/rules/Rule.md']);

        self::assertSame('../../../../../demo/pkg/doc/docs/guide.md.html', $links->resolve('../guide.md'));
        self::assertSame('../../../../../demo/pkg/doc/docs/rules/Rule.md.html', $links->resolve('./Rule.md'));
    }

    public function testResolveReturnsNullForTargetsOutsideTheRenderedDocuments(): void
    {
        $links = new MarkdownLinks(new SiteUrl(), 'demo/pkg', 'demo/pkg/index.html', '', ['README.md']);

        self::assertNull($links->resolve('docs/absent.md'));
        self::assertNull($links->resolve('src/Widget.php'));
        self::assertNull($links->resolve('https://example.com/README.md'));
        self::assertNull($links->resolve('mailto:demo@example.com'));
        self::assertNull($links->resolve('#section'));
        self::assertNull($links->resolve(''));
    }

    public function testNormalizeResolvesDotSegments(): void
    {
        $links = new MarkdownLinks(new SiteUrl(), 'demo/pkg', 'demo/pkg/index.html', '', []);

        self::assertSame('docs/guide.md', $links->normalize('docs/rules/../guide.md'));
        self::assertSame('guide.md', $links->normalize('./guide.md'));
        self::assertSame('guide.md', $links->normalize('../guide.md'));
    }
}

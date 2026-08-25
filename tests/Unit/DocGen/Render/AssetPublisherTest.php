<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\SiteFileWriter;
use PhpAiToolkit\DocGen\Render\AssetPublisher;
use PhpAiToolkit\DocGen\Render\Social\SocialCard;
use PhpAiToolkit\DocGen\Render\Social\SocialCardText;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Render\AssetPublisher
 * @uses \PhpAiToolkit\DocGen\DocGenException
 * @uses \PhpAiToolkit\DocGen\Filesystem\SiteFileWriter
 * @uses \PhpAiToolkit\DocGen\Render\Social\SocialCard
 * @uses \PhpAiToolkit\DocGen\Render\Social\SocialCardText
 */
#[CoversClass(AssetPublisher::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(SiteFileWriter::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialCardText::class)]
final class AssetPublisherTest extends TestCase
{
    public function testPublishWritesAssetsAndPagesMarker(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-assets-' . uniqid('', true);

        (new AssetPublisher())->publish($dir);

        self::assertFileExists($dir . '/assets/style.css');
        self::assertFileExists($dir . '/assets/app.js');
        self::assertFileExists($dir . '/.nojekyll');
        self::assertSame('', (string) file_get_contents($dir . '/.nojekyll'));
    }

    public function testAssetContentsReturnsBundledAssetText(): void
    {
        self::assertNotSame('', (new AssetPublisher())->assetContents('style.css'));
    }

    public function testPublishCardDrawsTheImageALinkIsPreviewedWith(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-card-' . uniqid('', true);

        (new AssetPublisher())->publishCard($dir, 'https://example.github.io/demo', 'demo/project', 'One sentence.');

        self::assertSame((new SocialCard())->supported(), file_exists($dir . '/' . SocialCard::PATH));
    }

    public function testPublishCardDrawsNothingForASiteWithoutAnAddress(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-card-' . uniqid('', true);

        (new AssetPublisher())->publishCard($dir, null, 'demo/project', 'One sentence.');

        self::assertFileDoesNotExist($dir . '/' . SocialCard::PATH);
    }

    public function testAssetContentsRejectsUnknownAsset(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Bundled asset not found:');

        (new AssetPublisher())->assetContents('missing.css');
    }
}

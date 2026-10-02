<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Filesystem\SiteFileWriter;
use Toolkit\DocGen\Render\AssetPublisher;
use Toolkit\DocGen\Render\Social\SocialCard;
use Toolkit\DocGen\Render\Social\SocialCardText;

/**
 * @covers \Toolkit\DocGen\Render\AssetPublisher
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Filesystem\SiteFileWriter
 * @uses \Toolkit\DocGen\Render\Social\SocialCard
 * @uses \Toolkit\DocGen\Render\Social\SocialCardText
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
        self::assertSame(
            '8874255e9deb2d159016522e985015be6a5d7a88c90c077704b5d762567b7ebd',
            hash_file('sha256', $dir . '/assets/document-design-v1.1.0.css'),
        );
        self::assertFileExists($dir . '/assets/document-design-LICENSE.txt');
        $notice = (string) file_get_contents($dir . '/assets/document-design-LICENSE.txt');
        self::assertStringContainsString('https://k-kinzal.github.io/document-design/v1.1.0/document-design.css', $notice);
        self::assertStringContainsString('SHA-256: 8874255e9deb2d159016522e985015be6a5d7a88c90c077704b5d762567b7ebd', $notice);
        self::assertStringContainsString('Tag commit: ee789d04fb775b735c3217d74019642485c9a297', $notice);
        self::assertSame(
            '390aeb080eabe1cf8c276d6c32e0520649301bbee2126c1647f7e409f47225ad',
            hash('sha256', (string) strstr($notice, "MIT License\n")),
        );
        self::assertFileExists($dir . '/assets/app.js');
        self::assertFileExists($dir . '/.nojekyll');
        self::assertSame('', (string) file_get_contents($dir . '/.nojekyll'));
    }

    public function testPublishPreservesStylesheetReferencedByArchivedDocument(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-archived-assets-' . uniqid('', true);
        $publisher = new AssetPublisher();
        $writer = new SiteFileWriter();
        $archive = '<link rel="stylesheet" href="assets/document-design-v1.0.0.css">';
        $writer->write($dir, 'archive.html', $archive);
        $writer->write($dir, 'assets/document-design-v1.0.0.css', $publisher->assetContents('document-design-v1.0.0.css'));

        $publisher->publish($dir);

        self::assertSame($archive, file_get_contents($dir . '/archive.html'));
        self::assertSame(
            '05f312d9faf6de35a0995cfa9434df1cab3a93c836a533307f9e23338a527793',
            hash_file('sha256', $dir . '/assets/document-design-v1.0.0.css'),
        );
        self::assertFileExists($dir . '/assets/document-design-v1.1.0.css');
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

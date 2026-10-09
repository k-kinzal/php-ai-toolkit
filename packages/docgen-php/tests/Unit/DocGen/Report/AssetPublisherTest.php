<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Report;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Report\AssetPublisher;
use Toolkit\DocGen\Report\Filesystem\SiteFileWriter;
use Toolkit\DocGen\Report\Social\SocialCard;
use Toolkit\DocGen\Report\Social\SocialCardText;

/**
 * @covers \Toolkit\DocGen\Report\AssetPublisher
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Report\Filesystem\SiteFileWriter
 * @uses \Toolkit\DocGen\Report\Social\SocialCard
 * @uses \Toolkit\DocGen\Report\Social\SocialCardText
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
            '17d7832ef632ad4c7101d479b3dbcc64beac0e7417c3ac51a66f99964f0bc918',
            hash_file('sha256', $dir . '/assets/document-design-v1.2.1.css'),
        );
        self::assertFileExists($dir . '/assets/document-design-LICENSE.txt');
        $notice = (string) file_get_contents($dir . '/assets/document-design-LICENSE.txt');
        self::assertStringContainsString('https://k-kinzal.github.io/document-design/v1.2.1/document-design.css', $notice);
        self::assertStringContainsString('SHA-256: 17d7832ef632ad4c7101d479b3dbcc64beac0e7417c3ac51a66f99964f0bc918', $notice);
        self::assertStringContainsString('Tag commit: 8a9032c7546db6dd9c55e38f15e6c9e388987fab', $notice);
        self::assertSame(
            '390aeb080eabe1cf8c276d6c32e0520649301bbee2126c1647f7e409f47225ad',
            hash('sha256', (string) strstr($notice, "MIT License\n")),
        );
        self::assertFileExists($dir . '/assets/app.js');
        self::assertFileExists($dir . '/.nojekyll');
        self::assertSame('', (string) file_get_contents($dir . '/.nojekyll'));
    }

    /**
     * @dataProvider providerArchivedStylesheets
     */
    #[DataProvider('providerArchivedStylesheets')]
    public function testPublishPreservesStylesheetReferencedByArchivedDocument(string $version, string $checksum): void
    {
        $dir = sys_get_temp_dir() . '/docgen-archived-assets-' . uniqid('', true);
        $publisher = new AssetPublisher();
        $writer = new SiteFileWriter();
        $asset = 'document-design-' . $version . '.css';
        $path = $dir . '/assets/' . $asset;
        $archive = '<link rel="stylesheet" href="assets/' . $asset . '">';
        $writer->write($dir, 'archive.html', $archive);
        $writer->write($dir, 'assets/' . $asset, $publisher->assetContents($asset));
        touch($path, 1000000000);

        $publisher->publish($dir);

        self::assertSame($archive, file_get_contents($dir . '/archive.html'));
        self::assertSame($checksum, hash_file('sha256', $path));
        clearstatcache(true, $path);
        self::assertSame(1000000000, filemtime($path));
        $notice = (string) file_get_contents($dir . '/assets/document-design-LICENSE.txt');
        self::assertStringContainsString($asset, $notice);
        self::assertStringContainsString('SHA-256: ' . $checksum, $notice);
        self::assertFileExists($dir . '/assets/document-design-v1.2.1.css');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function providerArchivedStylesheets(): array
    {
        return [
            'v1.0.0' => ['v1.0.0', '05f312d9faf6de35a0995cfa9434df1cab3a93c836a533307f9e23338a527793'],
            'v1.1.0' => ['v1.1.0', '8874255e9deb2d159016522e985015be6a5d7a88c90c077704b5d762567b7ebd'],
        ];
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

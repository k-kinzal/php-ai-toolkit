<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\SiteFileWriter;
use PhpAiToolkit\DocGen\Render\AssetPublisher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssetPublisher::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(SiteFileWriter::class)]
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

    public function testAssetContentsRejectsUnknownAsset(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Bundled asset not found:');

        (new AssetPublisher())->assetContents('missing.css');
    }
}

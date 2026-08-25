<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Document;

use PhpAiToolkit\DocGen\Analysis\Document\DocumentCollector;
use PhpAiToolkit\DocGen\Analysis\Model\MarkdownDoc;
use PhpAiToolkit\DocGen\Config\DocGenConfig;
use PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver;
use PhpAiToolkit\DocGen\Filesystem\MarkdownFileFinder;
use PhpAiToolkit\DocGen\Filesystem\SourceFileFinder;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Document\DocumentCollector
 * @uses \PhpAiToolkit\DocGen\Package\ComposerManifest
 * @uses \PhpAiToolkit\DocGen\Package\DiscoveredPackage
 * @uses \PhpAiToolkit\DocGen\Config\DocGenConfig
 * @uses \PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\MarkdownDoc
 * @uses \PhpAiToolkit\DocGen\Filesystem\MarkdownFileFinder
 * @uses \PhpAiToolkit\DocGen\Filesystem\SourceFileFinder
 */
#[CoversClass(DocumentCollector::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocGenConfig::class)]
#[UsesClass(DocGenPathResolver::class)]
#[UsesClass(MarkdownDoc::class)]
#[UsesClass(MarkdownFileFinder::class)]
#[UsesClass(SourceFileFinder::class)]
final class DocumentCollectorTest extends TestCase
{
    public function testCollectReadsRepositoryMarkdownAndSkipsVendorPackages(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-documents-' . bin2hex(random_bytes(4));
        mkdir($dir . '/docs', 0777, true);
        mkdir($dir . '/vendor/acme/lib', 0777, true);
        file_put_contents($dir . '/README.md', "# Demo App\n");
        file_put_contents($dir . '/docs/guide.md', "# Guide\n");
        file_put_contents($dir . '/vendor/acme/lib/README.md', "# Vendor\n");
        $root = (string) realpath($dir);
        $project = new DiscoveredPackage(new ComposerManifest($root, 'demo/app', '', [], [], [], [], []), false);
        $vendor = new DiscoveredPackage(new ComposerManifest($root . '/vendor/acme/lib', 'acme/lib', '', [], [], [], [], []), true);
        $config = new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, null);

        $documents = (new DocumentCollector())->collect($config, [$project, $vendor]);

        self::assertCount(2, $documents);
        self::assertSame('demo/app', $documents[0]->packageName);
        self::assertSame('README.md', $documents[0]->path);
        self::assertSame('README.md', $documents[0]->file);
        self::assertSame('Demo App', $documents[0]->title);
        self::assertSame('docs/guide.md', $documents[1]->path);
        self::assertSame('Guide', $documents[1]->title);
    }

    public function testCollectHonorsExcludeGlobs(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-documents-' . bin2hex(random_bytes(4));
        mkdir($dir . '/docs', 0777, true);
        file_put_contents($dir . '/README.md', "# Demo App\n");
        file_put_contents($dir . '/docs/internal.md', "# Internal\n");
        $root = (string) realpath($dir);
        $project = new DiscoveredPackage(new ComposerManifest($root, 'demo/app', '', [], [], [], [], []), false);
        $config = new DocGenConfig($root, ['.'], [], ['docs'], 'build/docs', null, null, null);

        $documents = (new DocumentCollector())->collect($config, [$project]);

        self::assertCount(1, $documents);
        self::assertSame('README.md', $documents[0]->path);
    }

    public function testTitleReadsTheFirstHeadingOutsideCodeFences(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-documents-' . bin2hex(random_bytes(4));
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/fenced.md', "```sh\n# not a title\n```\n\n#  Real Title  \n");
        file_put_contents($dir . '/plain.md', "Just text.\n");

        self::assertSame('Real Title', (new DocumentCollector())->title($dir . '/fenced.md', 'fenced.md'));
        self::assertSame('plain.md', (new DocumentCollector())->title($dir . '/plain.md', 'plain.md'));
        self::assertSame('absent.md', (new DocumentCollector())->title($dir . '/absent.md', 'absent.md'));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Discovery\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Action\Config\DocGenConfig;
use Toolkit\DocGen\Action\GenerateDocumentation;
use Toolkit\DocGen\Action\GenerationRequest;
use Toolkit\DocGen\Action\GenerationResult;
use Toolkit\DocGen\Analysis\AnalysisOptions;
use Toolkit\DocGen\Analysis\ProjectAnalyzer;
use Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver;
use Toolkit\DocGen\Discovery\Internal\DocumentCollector;
use Toolkit\DocGen\Discovery\Internal\Filesystem\MarkdownFileFinder;
use Toolkit\DocGen\Discovery\Internal\Filesystem\SourceFileFinder;
use Toolkit\DocGen\Discovery\MarkdownDoc;
use Toolkit\DocGen\Discovery\Package\ComposerManifest;
use Toolkit\DocGen\Discovery\Package\DiscoveredPackage;
use Toolkit\DocGen\Discovery\SourceDiscovery;
use Toolkit\DocGen\Discovery\SourceFile;
use Toolkit\DocGen\Discovery\SourceSelection;
use Toolkit\DocGen\Discovery\SourceSet;
use Toolkit\DocGen\Parse\ParsedProject;
use Toolkit\DocGen\Report\RenderedSite;

/**
 * @covers \Toolkit\DocGen\Discovery\Internal\DocumentCollector
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Action\Config\DocGenConfig
 * @uses \Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver
 * @uses \Toolkit\DocGen\Discovery\MarkdownDoc
 * @uses \Toolkit\DocGen\Discovery\Internal\Filesystem\MarkdownFileFinder
 * @uses \Toolkit\DocGen\Discovery\Internal\Filesystem\SourceFileFinder
 * @uses \Toolkit\DocGen\Discovery\SourceSelection
 * @uses \Toolkit\DocGen\Discovery\SourceFile
 * @uses \Toolkit\DocGen\Discovery\SourceSet
 * @uses \Toolkit\DocGen\Discovery\SourceDiscovery
 * @uses \Toolkit\DocGen\Parse\ParsedProject
 * @uses \Toolkit\DocGen\Analysis\AnalysisOptions
 * @uses \Toolkit\DocGen\Analysis\ProjectAnalyzer
 * @uses \Toolkit\DocGen\Action\GenerateDocumentation
 * @uses \Toolkit\DocGen\Action\GenerationRequest
 * @uses \Toolkit\DocGen\Action\GenerationResult
 * @uses \Toolkit\DocGen\Report\RenderedSite
 */
#[CoversClass(DocumentCollector::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocGenConfig::class)]
#[UsesClass(DocGenPathResolver::class)]
#[UsesClass(MarkdownDoc::class)]
#[UsesClass(MarkdownFileFinder::class)]
#[UsesClass(SourceFileFinder::class)]
#[UsesClass(SourceSelection::class)]
#[UsesClass(SourceFile::class)]
#[UsesClass(SourceSet::class)]
#[UsesClass(SourceDiscovery::class)]
#[UsesClass(ParsedProject::class)]
#[UsesClass(AnalysisOptions::class)]
#[UsesClass(ProjectAnalyzer::class)]
#[UsesClass(GenerateDocumentation::class)]
#[UsesClass(GenerationRequest::class)]
#[UsesClass(GenerationResult::class)]
#[UsesClass(RenderedSite::class)]
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
        $config = new SourceSelection($root, ['.'], [], []);

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
        $config = new SourceSelection($root, ['.'], [], ['docs']);

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

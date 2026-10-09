<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Discovery;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver;
use Toolkit\DocGen\Discovery\Internal\DocumentCollector;
use Toolkit\DocGen\Discovery\Internal\Filesystem\MarkdownFileFinder;
use Toolkit\DocGen\Discovery\Internal\Filesystem\SourceFileFinder;
use Toolkit\DocGen\Discovery\Internal\Package\ComposerManifestReader;
use Toolkit\DocGen\Discovery\Internal\Package\DevPackageResolver;
use Toolkit\DocGen\Discovery\Internal\Package\PackageDiscovery;
use Toolkit\DocGen\Discovery\MarkdownDoc;
use Toolkit\DocGen\Discovery\Package\ComposerManifest;
use Toolkit\DocGen\Discovery\Package\DiscoveredPackage;
use Toolkit\DocGen\Discovery\Package\RepositoryAddress;
use Toolkit\DocGen\Discovery\SourceDiscovery;
use Toolkit\DocGen\Discovery\SourceFile;
use Toolkit\DocGen\Discovery\SourceSelection;
use Toolkit\DocGen\Discovery\SourceSet;
use Toolkit\DocGen\DocGenException;

/**
 * @covers \Toolkit\DocGen\Discovery\SourceSet
 * @uses \Toolkit\DocGen\Discovery\SourceSelection
 * @uses \Toolkit\DocGen\Discovery\SourceFile
 * @uses \Toolkit\DocGen\Discovery\SourceDiscovery
 * @uses \Toolkit\DocGen\Discovery\MarkdownDoc
 * @uses \Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver
 * @uses \Toolkit\DocGen\Discovery\Internal\DocumentCollector
 * @uses \Toolkit\DocGen\Discovery\Internal\Filesystem\SourceFileFinder
 * @uses \Toolkit\DocGen\Discovery\Internal\Filesystem\MarkdownFileFinder
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\PackageDiscovery
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\ComposerManifestReader
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\DevPackageResolver
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Discovery\Package\RepositoryAddress
 * @uses \Toolkit\DocGen\DocGenException
 */
#[CoversClass(SourceSet::class)]
#[UsesClass(SourceSelection::class)]
#[UsesClass(SourceFile::class)]
#[UsesClass(SourceDiscovery::class)]
#[UsesClass(MarkdownDoc::class)]
#[UsesClass(DocGenPathResolver::class)]
#[UsesClass(DocumentCollector::class)]
#[UsesClass(SourceFileFinder::class)]
#[UsesClass(MarkdownFileFinder::class)]
#[UsesClass(PackageDiscovery::class)]
#[UsesClass(ComposerManifestReader::class)]
#[UsesClass(DevPackageResolver::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(RepositoryAddress::class)]
#[UsesClass(DocGenException::class)]
final class SourceSetTest extends TestCase
{
    public function testSourceFilesListsEveryPackageSourceOnceInDiscoveryOrder(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-collector-' . bin2hex(random_bytes(4));
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/src/Alpha.php', "<?php\n\nnamespace Demo;\n\nclass Alpha\n{\n}\n");
        file_put_contents($dir . '/src/Beta.php', "<?php\n\nnamespace Demo;\n\nclass Beta\n{\n}\n");
        $root = (string) realpath($dir);
        $manifest = new ComposerManifest($root, 'demo/app', '', ['Demo\\' => ['src']], [], [], [], [], ['src']);
        $config = new SourceSelection($root, ['.'], [], []);

        file_put_contents($root . '/composer.json', '{"name":"demo/app","autoload":{"psr-4":{"Demo\\\\":"src"},"classmap":["src"]}}');
        $sources = (new SourceDiscovery())->discover($config);
        $files = $sources->files;

        self::assertCount(2, $files);
        self::assertSame($root . '/src/Alpha.php', $files[0]->path);
        self::assertSame($root . '/src/Beta.php', $files[1]->path);
        self::assertSame('demo/app', $files[0]->packageName);
        self::assertFalse($files[0]->isDev);
    }
}

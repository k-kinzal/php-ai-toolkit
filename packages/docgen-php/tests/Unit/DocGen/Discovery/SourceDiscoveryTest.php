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
 * @covers \Toolkit\DocGen\Discovery\SourceDiscovery
 * @uses \Toolkit\DocGen\Discovery\SourceSelection
 * @uses \Toolkit\DocGen\Discovery\SourceFile
 * @uses \Toolkit\DocGen\Discovery\SourceSet
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
#[CoversClass(SourceDiscovery::class)]
#[UsesClass(SourceSelection::class)]
#[UsesClass(SourceFile::class)]
#[UsesClass(SourceSet::class)]
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
final class SourceDiscoveryTest extends TestCase
{
    public function testSourceFilesReturnsNothingWhenNoSourcesWereSelected(): void
    {
        self::assertSame([], (new SourceDiscovery())->sourceFiles(new SourceSelection('/unused', [], [], []), []));
    }

    public function testDiscoverCompletesSelectionBeforeParsing(): void
    {
        $root = sys_get_temp_dir() . '/docgen-discovery-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/composer.json', '{"name":"demo/app","autoload":{"psr-4":{"Demo\\\\":"src"}}}');
        file_put_contents($root . '/src/Broken.php', '<?php class {');
        file_put_contents($root . '/README.md', '# Example');

        $sources = (new SourceDiscovery())->discover(new SourceSelection($root, ['.'], [], []));

        self::assertSame('src/Broken.php', $sources->files[0]->relative);
        self::assertSame('demo/app', $sources->files[0]->packageName);
        self::assertSame('Example', $sources->documents[0]->title);
        self::assertSame([], $sources->warnings);
    }

    public function testSourceDirectoriesListsAutoloadAndDevAutoloadDirectories(): void
    {
        $manifest = new ComposerManifest('/tmp/demo', 'demo/app', '', ['Demo\\' => ['src']], ['DemoTests\\' => ['tests']], [], [], []);

        $sources = (new SourceDiscovery())->sourceDirectories(new DiscoveredPackage($manifest, false));

        self::assertSame([
            ['directory' => '/tmp/demo/src', 'isDev' => false],
            ['directory' => '/tmp/demo/tests', 'isDev' => true],
        ], $sources);
    }
    public function testSourceDirectoriesAddsExistingClassmapDirectories(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-analyzer-' . bin2hex(random_bytes(4));
        mkdir($dir . '/lib/legacy', 0777, true);
        mkdir($dir . '/tests/Fixture', 0777, true);
        file_put_contents($dir . '/Bootstrap.php', '<?php');
        $manifest = new ComposerManifest($dir, 'demo/app', '', ['Demo\\' => ['src']], [], [], [], [], ['lib/legacy', 'Bootstrap.php', 'missing'], ['tests/Fixture']);

        $sources = (new SourceDiscovery())->sourceDirectories(new DiscoveredPackage($manifest, false));

        self::assertSame([
            ['directory' => $dir . '/src', 'isDev' => false],
            ['directory' => $dir . '/lib/legacy', 'isDev' => false],
            ['directory' => $dir . '/tests/Fixture', 'isDev' => true],
        ], $sources);
    }
    public function testSourceDirectoriesMapsEmptyPsr4PathToPackageRoot(): void
    {
        $manifest = new ComposerManifest('/tmp/demo/vendor/symfony/yaml', 'symfony/yaml', '', ['Symfony\\Component\\Yaml\\' => ['']], [], [], [], []);

        $sources = (new SourceDiscovery())->sourceDirectories(new DiscoveredPackage($manifest, true));

        self::assertSame([['directory' => '/tmp/demo/vendor/symfony/yaml', 'isDev' => false]], $sources);
    }
    public function testSourceDirectoriesReturnsNothingForPharOnlyPackage(): void
    {
        $manifest = new ComposerManifest('/tmp/demo/vendor/phpstan/phpstan', 'phpstan/phpstan', '', [], [], [], [], []);

        self::assertSame([], (new SourceDiscovery())->sourceDirectories(new DiscoveredPackage($manifest, true)));
    }
    public function testVendorWarningsReportsGlobThatMatchedNoPackage(): void
    {
        $config = new SourceSelection('/tmp/demo', ['.'], ['vendor'], [], ['dev-vendor']);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/demo', 'demo/app', '', [], [], [], [], []), false);

        $warnings = (new SourceDiscovery())->vendorWarnings($config, [$package]);

        self::assertCount(2, $warnings);
        self::assertSame(
            'Vendor glob "vendor" documented no installed runtime vendor package. Vendor globs match composer package names such as "acme/lib" or "acme/*", not directory names.',
            $warnings[0],
        );
        self::assertSame(
            'Vendor glob "dev-vendor" documented no installed dev vendor package. Vendor globs match composer package names such as "acme/lib" or "acme/*", not directory names.',
            $warnings[1],
        );
    }
    public function testVendorWarningsStaysSilentForMatchingGlob(): void
    {
        $config = new SourceSelection('/tmp/demo', ['.'], ['acme/*'], [], ['phpunit/*']);
        $runtime = new DiscoveredPackage(new ComposerManifest('/tmp/demo/vendor/acme/lib', 'acme/lib', '', ['Acme\\' => ['src']], [], [], [], []), true);
        $dev = new DiscoveredPackage(new ComposerManifest('/tmp/demo/vendor/phpunit/phpunit', 'phpunit/phpunit', '', ['PHPUnit\\' => ['src']], [], [], [], []), true, true);

        self::assertSame([], (new SourceDiscovery())->vendorWarnings($config, [$runtime, $dev]));
    }
    public function testVendorWarningsReportsVendorPackageWithoutSources(): void
    {
        $config = new SourceSelection('/tmp/demo', ['.'], ['phpstan/*'], []);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/demo/vendor/phpstan/phpstan', 'phpstan/phpstan', '', [], [], [], [], []), true);

        $warnings = (new SourceDiscovery())->vendorWarnings($config, [$package]);

        self::assertCount(1, $warnings);
        self::assertSame(
            'Vendor package "phpstan/phpstan" declares no PSR-4 or classmap autoload source, so its classes cannot be documented or linked. Packages that autoload only "files" entries, such as a phar bootstrap, cannot be documented: drop "phpstan/phpstan" from the vendor globs.',
            $warnings[0],
        );
    }
    public function testVendorGlobWarningsIgnoresPackagesOfTheOtherDependencyKind(): void
    {
        $devPackage = new DiscoveredPackage(new ComposerManifest('/tmp/demo/vendor/phpunit/phpunit', 'phpunit/phpunit', '', ['PHPUnit\\' => ['src']], [], [], [], []), true, true);

        $warnings = (new SourceDiscovery())->vendorGlobWarnings(['phpunit/*'], [$devPackage], false);

        self::assertCount(1, $warnings);
        self::assertStringContainsString('documented no installed runtime vendor package', $warnings[0]);
        self::assertSame([], (new SourceDiscovery())->vendorGlobWarnings(['phpunit/*'], [$devPackage], true));
    }
    public function testVendorSourceWarningsIgnoresProjectPackagesAndDocumentedVendors(): void
    {
        $project = new DiscoveredPackage(new ComposerManifest('/tmp/demo', 'demo/app', '', [], [], [], [], []), false);
        $vendor = new DiscoveredPackage(new ComposerManifest('/tmp/demo/vendor/acme/lib', 'acme/lib', '', ['Acme\\' => ['src']], [], [], [], []), true);

        self::assertSame([], (new SourceDiscovery())->vendorSourceWarnings([$project, $vendor]));
    }
}

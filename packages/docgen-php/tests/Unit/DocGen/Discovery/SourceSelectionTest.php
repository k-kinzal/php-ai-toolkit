<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Discovery;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Action\Config\DocGenConfig;
use Toolkit\DocGen\Action\Config\RepositoryUrl;
use Toolkit\DocGen\Action\GenerateDocumentation;
use Toolkit\DocGen\Action\GenerationRequest;
use Toolkit\DocGen\Action\GenerationResult;
use Toolkit\DocGen\Analysis\AnalysisOptions;
use Toolkit\DocGen\Analysis\ProjectAnalyzer;
use Toolkit\DocGen\Discovery\Internal\Package\ComposerLockReader;
use Toolkit\DocGen\Discovery\Internal\Package\ComposerManifestReader;
use Toolkit\DocGen\Discovery\Internal\Package\DevPackageResolver;
use Toolkit\DocGen\Discovery\Internal\Package\PackageDiscovery;
use Toolkit\DocGen\Discovery\Internal\Package\VendorPackageLocator;
use Toolkit\DocGen\Discovery\Package\ComposerManifest;
use Toolkit\DocGen\Discovery\Package\DiscoveredPackage;
use Toolkit\DocGen\Discovery\Package\RepositoryAddress;
use Toolkit\DocGen\Discovery\SourceDiscovery;
use Toolkit\DocGen\Discovery\SourceFile;
use Toolkit\DocGen\Discovery\SourceSelection;
use Toolkit\DocGen\Discovery\SourceSet;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Parse\ParsedProject;
use Toolkit\DocGen\Report\RenderedSite;

/**
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\PackageDiscovery
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\ComposerLockReader
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\ComposerManifestReader
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\DevPackageResolver
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Action\Config\DocGenConfig
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Action\Config\RepositoryUrl
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\VendorPackageLocator
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
 * @covers \Toolkit\DocGen\Discovery\SourceSelection
 * @uses \Toolkit\DocGen\Discovery\Package\RepositoryAddress
 */
#[UsesClass(PackageDiscovery::class)]
#[UsesClass(ComposerLockReader::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ComposerManifestReader::class)]
#[UsesClass(DevPackageResolver::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocGenConfig::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(RepositoryUrl::class)]
#[UsesClass(VendorPackageLocator::class)]

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
#[CoversClass(SourceSelection::class)]
#[UsesClass(RepositoryAddress::class)]
final class SourceSelectionTest extends TestCase
{
    public function testDiscoverAppendsMatchingDevVendorPackagesForVendorDevGlobs(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-discovery-' . uniqid('', true);
        mkdir($dir . '/vendor/acme/lib', 0777, true);
        mkdir($dir . '/vendor/acme/tool', 0777, true);
        file_put_contents($dir . '/composer.json', '{"name": "acme/root"}');
        file_put_contents($dir . '/composer.lock', '{"packages": [{"name": "acme/lib"}], "packages-dev": [{"name": "acme/tool"}]}');
        file_put_contents($dir . '/vendor/acme/lib/composer.json', '{"name": "acme/lib"}');
        file_put_contents($dir . '/vendor/acme/tool/composer.json', '{"name": "acme/tool"}');
        $config = new SourceSelection($dir, ['.'], ['acme/lib'], [], ['acme/*']);

        $packages = (new PackageDiscovery())->discover($config);

        self::assertCount(3, $packages);
        self::assertSame('acme/lib', $packages[1]->manifest->name);
        self::assertFalse($packages[1]->isDevDependency);
        self::assertSame('acme/tool', $packages[2]->manifest->name);
        self::assertTrue($packages[2]->isVendor);
        self::assertTrue($packages[2]->isDevDependency);
    }
}

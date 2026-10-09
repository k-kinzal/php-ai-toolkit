<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Action;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Action\Config\DocGenConfig;
use Toolkit\DocGen\Action\Config\RepositoryUrl;
use Toolkit\DocGen\Action\GenerateDocumentation;
use Toolkit\DocGen\Action\GenerationRequest;
use Toolkit\DocGen\Action\GenerationResult;
use Toolkit\DocGen\Action\ProjectAnalysis;
use Toolkit\DocGen\Analysis\AnalysisOptions;
use Toolkit\DocGen\Analysis\Coverage\CoverageIndex;
use Toolkit\DocGen\Analysis\Coverage\CoverageReader;
use Toolkit\DocGen\Analysis\Coverage\MethodCoverage;
use Toolkit\DocGen\Analysis\Layer\DeptracConfigReader;
use Toolkit\DocGen\Analysis\Layer\LayerAssigner;
use Toolkit\DocGen\Analysis\Layer\LayerCollector;
use Toolkit\DocGen\Analysis\Layer\LayerDefinition;
use Toolkit\DocGen\Analysis\Layer\LayerModel;
use Toolkit\DocGen\Analysis\Package\PackageGraph;
use Toolkit\DocGen\Analysis\Package\PackageGraphBuilder;
use Toolkit\DocGen\Analysis\ProjectAnalyzer;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCase as ReferenceTestCase;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Cache\ToolkitFingerprint;
use Toolkit\DocGen\Discovery\DocumentCollector;
use Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver;
use Toolkit\DocGen\Discovery\Filesystem\MarkdownFileFinder;
use Toolkit\DocGen\Discovery\Filesystem\SourceFileFinder;
use Toolkit\DocGen\Discovery\Package\ComposerLockReader;
use Toolkit\DocGen\Discovery\Package\ComposerManifest;
use Toolkit\DocGen\Discovery\Package\ComposerManifestReader;
use Toolkit\DocGen\Discovery\Package\DevPackageResolver;
use Toolkit\DocGen\Discovery\Package\DiscoveredPackage;
use Toolkit\DocGen\Discovery\Package\PackageDiscovery;
use Toolkit\DocGen\Discovery\Package\RepositoryAddress;
use Toolkit\DocGen\Discovery\Package\VendorPackageLocator;
use Toolkit\DocGen\Discovery\SourceDiscovery;
use Toolkit\DocGen\Discovery\SourceFile;
use Toolkit\DocGen\Discovery\SourceSelection;
use Toolkit\DocGen\Discovery\SourceSet;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Parallel\CpuCoreCounter;
use Toolkit\DocGen\Parallel\WorkerCount;
use Toolkit\DocGen\Parallel\WorkerPool;
use Toolkit\DocGen\Parallel\WorkScheduler;
use Toolkit\DocGen\Parse\AstParser;
use Toolkit\DocGen\Parse\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Parse\Builder\ConstantBuilder;
use Toolkit\DocGen\Parse\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Parse\Builder\FunctionBuilder;
use Toolkit\DocGen\Parse\Builder\MethodBuilder;
use Toolkit\DocGen\Parse\Builder\ParameterBuilder;
use Toolkit\DocGen\Parse\Builder\PropertyBuilder;
use Toolkit\DocGen\Parse\Cache\SourceFileKey;
use Toolkit\DocGen\Parse\Doc\DocBlockReader;
use Toolkit\DocGen\Parse\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Parse\ExprTextPrinter;
use Toolkit\DocGen\Parse\FileSymbolCollector;
use Toolkit\DocGen\Parse\NativeTypePrinter;
use Toolkit\DocGen\Parse\ParameterModifiers;
use Toolkit\DocGen\Parse\ParsedProject;
use Toolkit\DocGen\Parse\PhpParserBridge;
use Toolkit\DocGen\Parse\ProjectSymbolCollector;
use Toolkit\DocGen\Parse\Reference\LocalTypeMap;
use Toolkit\DocGen\Parse\Reference\PropertyTypeScanner;
use Toolkit\DocGen\Parse\Reference\Usage;
use Toolkit\DocGen\Parse\Reference\UsageCollector;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Parse\Symbol\FileSymbols;
use Toolkit\DocGen\Parse\Symbol\MethodDoc;
use Toolkit\DocGen\Parse\Symbol\ParameterDoc;
use Toolkit\DocGen\Parse\Symbol\TypeSignature;
use Toolkit\DocGen\Parse\SymbolContext;
use Toolkit\DocGen\Parse\UseMapCollector;
use Toolkit\DocGen\Report\RenderedSite;

/**
 * @covers \Toolkit\DocGen\Action\ProjectAnalysis
 * @uses \Toolkit\DocGen\Parse\AstParser
 * @uses \Toolkit\DocGen\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerLockReader
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifestReader
 * @uses \Toolkit\DocGen\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Analysis\Coverage\CoverageIndex
 * @uses \Toolkit\DocGen\Analysis\Coverage\CoverageReader
 * @uses \Toolkit\DocGen\Parallel\CpuCoreCounter
 * @uses \Toolkit\DocGen\Analysis\Layer\DeptracConfigReader
 * @uses \Toolkit\DocGen\Discovery\Package\DevPackageResolver
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Parse\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Action\Config\DocGenConfig
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver
 * @uses \Toolkit\DocGen\Discovery\DocumentCollector
 * @uses \Toolkit\DocGen\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerAssigner
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerCollector
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerDefinition
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerModel
 * @uses \Toolkit\DocGen\Parse\Reference\LocalTypeMap
 * @uses \Toolkit\DocGen\Discovery\Filesystem\MarkdownFileFinder
 * @uses \Toolkit\DocGen\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Analysis\Coverage\MethodCoverage
 * @uses \Toolkit\DocGen\Parse\Symbol\MethodDoc
 * @uses \Toolkit\DocGen\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Discovery\Package\PackageDiscovery
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraphBuilder
 * @uses \Toolkit\DocGen\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ParameterDoc
 * @uses \Toolkit\DocGen\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Parse\ProjectSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Parse\Reference\PropertyTypeScanner
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCase
 * @uses \Toolkit\DocGen\Action\Config\RepositoryUrl
 * @uses \Toolkit\DocGen\Discovery\Filesystem\SourceFileFinder
 * @uses \Toolkit\DocGen\Parse\Cache\SourceFileKey
 * @uses \Toolkit\DocGen\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Cache\ToolkitFingerprint
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Parse\Reference\Usage
 * @uses \Toolkit\DocGen\Parse\Reference\UsageCollector
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Parse\UseMapCollector
 * @uses \Toolkit\DocGen\Discovery\Package\VendorPackageLocator
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
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
 * @uses \Toolkit\DocGen\Discovery\Package\RepositoryAddress
 */
#[CoversClass(ProjectAnalysis::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ComposerLockReader::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ComposerManifestReader::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(CoverageIndex::class)]
#[UsesClass(CoverageReader::class)]
#[UsesClass(CpuCoreCounter::class)]
#[UsesClass(DeptracConfigReader::class)]
#[UsesClass(DevPackageResolver::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocGenConfig::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(DocGenPathResolver::class)]
#[UsesClass(DocumentCollector::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(LayerAssigner::class)]
#[UsesClass(LayerCollector::class)]
#[UsesClass(LayerDefinition::class)]
#[UsesClass(LayerModel::class)]
#[UsesClass(LocalTypeMap::class)]
#[UsesClass(MarkdownFileFinder::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(MethodCoverage::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(PackageDiscovery::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PackageGraphBuilder::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(ProjectSymbolCollector::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(PropertyTypeScanner::class)]
#[UsesClass(ReferenceTestCase::class)]
#[UsesClass(RepositoryUrl::class)]
#[UsesClass(SourceFileFinder::class)]
#[UsesClass(SourceFileKey::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(ToolkitFingerprint::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(Usage::class)]
#[UsesClass(UsageCollector::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UseMapCollector::class)]
#[UsesClass(VendorPackageLocator::class)]
#[UsesClass(WorkScheduler::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
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
#[UsesClass(RepositoryAddress::class)]
final class ProjectAnalysisTest extends TestCase
{
    public function testAnalyzeBuildsModelFromTinyComposerProject(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-analyzer-' . bin2hex(random_bytes(4));
        mkdir($dir . '/src', 0777, true);
        mkdir($dir . '/tests', 0777, true);
        file_put_contents($dir . '/composer.json', <<<'JSON'
{
    "name": "demo/app",
    "autoload": {"psr-4": {"Demo\\": "src/"}},
    "autoload-dev": {"psr-4": {"DemoTests\\": "tests/"}}
}
JSON);
        file_put_contents($dir . '/src/GreeterContract.php', <<<'PHP'
<?php

namespace Demo;

interface GreeterContract
{
    public function greet(string $name): string;
}
PHP);
        file_put_contents($dir . '/src/Greeter.php', <<<'PHP'
<?php

namespace Demo;

class Greeter implements GreeterContract
{
    public function greet(string $name): string
    {
        return 'Hello ' . $name;
    }
}
PHP);
        file_put_contents($dir . '/tests/GreeterTest.php', <<<'PHP'
<?php

namespace DemoTests;

use Demo\Greeter;

class GreeterTest
{
    public function check(): string
    {
        $greeter = new Greeter();

        return $greeter->greet('AI');
    }
}
PHP);
        $root = (string) realpath($dir);

        $model = (new ProjectAnalysis())->analyze(new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, null));

        self::assertSame('demo/app', $model->title);
        self::assertCount(1, $model->packages);
        self::assertCount(3, $model->classLikes);
        self::assertSame('Demo\Greeter', $model->classLikes[0]->fqcn);
        self::assertFalse($model->classLikes[0]->isDev);
        self::assertSame('Demo\GreeterContract', $model->classLikes[1]->fqcn);
        self::assertFalse($model->classLikes[1]->isDev);
        self::assertSame('DemoTests\GreeterTest', $model->classLikes[2]->fqcn);
        self::assertTrue($model->classLikes[2]->isDev);
        self::assertNotNull($model->symbolTable->classLike('\DEMO\GreeterContract'));
        self::assertSame(['Demo\Greeter'], $model->hierarchy->implementorsOf('Demo\GreeterContract'));
        self::assertCount(2, $model->usages->forType('Demo\Greeter'));
        self::assertNull($model->layers);
        self::assertSame([], $model->layerAssignments);
        self::assertNull($model->coverage);
        self::assertSame([], $model->warnings);
    }

    public function testAnalyzeCollectsWarningForUnparsableSource(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-analyzer-' . bin2hex(random_bytes(4));
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/composer.json', <<<'JSON'
{
    "name": "demo/app",
    "autoload": {"psr-4": {"Demo\\": "src/"}}
}
JSON);
        file_put_contents($dir . '/src/Valid.php', <<<'PHP'
<?php

namespace Demo;

class Valid
{
}
PHP);
        file_put_contents($dir . '/src/Broken.php', '<?php class {');
        $root = (string) realpath($dir);

        $model = (new ProjectAnalysis())->analyze(new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, null));

        self::assertCount(1, $model->warnings);
        self::assertStringContainsString('Failed to parse src/Broken.php', $model->warnings[0]);
        self::assertCount(1, $model->classLikes);
        self::assertSame('Demo\Valid', $model->classLikes[0]->fqcn);
    }

    public function testAnalyzeReadsCoverageReportWhenConfigured(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-analyzer-' . bin2hex(random_bytes(4));
        mkdir($dir . '/src', 0777, true);
        mkdir($dir . '/coverage-xml', 0777, true);
        file_put_contents($dir . '/composer.json', <<<'JSON'
{
    "name": "demo/app",
    "autoload": {"psr-4": {"Demo\\": "src/"}}
}
JSON);
        file_put_contents($dir . '/src/Greeter.php', <<<'PHP'
<?php

namespace Demo;

class Greeter
{
    public function greet(): string
    {
        return 'Hello';
    }
}
PHP);
        file_put_contents($dir . '/coverage-xml/Greeter.php.xml', <<<'XML'
<?xml version="1.0"?>
<phpunit>
  <file name="Greeter.php" path="src">
    <method name="greet" start="7" executable="1" executed="1" coverage="100"/>
    <coverage>
      <line nr="9">
        <covered by="DemoTests\GreeterTest::testGreet"/>
      </line>
    </coverage>
  </file>
</phpunit>
XML);
        $root = (string) realpath($dir);

        $model = (new ProjectAnalysis())->analyze(new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, 'coverage-xml'));

        $coverage = $model->coverage;

        self::assertNotNull($coverage);
        self::assertSame(['DemoTests\GreeterTest::testGreet'], $coverage->testsForRange('src/Greeter.php', 1, 100));
        $method = $coverage->methodAt('src/Greeter.php', 1, 100);
        self::assertNotNull($method);
        self::assertSame(1, $method->executable);
    }

    public function testTitleForPrefersConfiguredTitle(): void
    {
        $config = new DocGenConfig('/tmp/demo', ['.'], [], [], 'build/docs', 'Custom Title', null, null);

        self::assertSame('Custom Title', (new ProjectAnalysis())->titleFor($config, []));
    }

    public function testTitleForFallsBackToRootBasenameWithoutRootPackage(): void
    {
        $config = new DocGenConfig('/tmp/demo-docs', ['.'], [], [], 'build/docs', null, null, null);
        $vendorPackage = new DiscoveredPackage(new ComposerManifest('/tmp/other', 'vendor/lib', '', [], [], [], [], []), true);

        self::assertSame('demo-docs', (new ProjectAnalysis())->titleFor($config, [$vendorPackage]));
    }

    public function testRepositoryForPrefersTheConfiguredAddress(): void
    {
        $config = new DocGenConfig('/tmp/demo', ['.'], [], [], 'build/docs', null, null, null, [], null, null, 'https://github.com/example/configured');
        $root = new DiscoveredPackage(new ComposerManifest('/tmp/demo', 'demo/app', '', [], [], [], [], [], [], [], 'https://github.com/example/declared'), false);

        self::assertSame('https://github.com/example/configured', (new ProjectAnalysis())->repositoryFor($config, [$root]));
    }

    public function testRepositoryForReadsTheRootPackageWhenNothingIsConfigured(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-repository-' . uniqid('', true);
        mkdir($directory, 0777, true);
        $config = new DocGenConfig($directory, ['.'], [], [], 'build/docs', null, null, null);
        $vendor = new DiscoveredPackage(new ComposerManifest($directory, 'acme/lib', '', [], [], [], [], [], [], [], 'https://github.com/acme/lib'), true);
        $root = new DiscoveredPackage(new ComposerManifest($directory, 'demo/app', '', [], [], [], [], [], [], [], 'https://github.com/example/declared'), false);

        self::assertSame('https://github.com/example/declared', (new ProjectAnalysis())->repositoryFor($config, [$vendor, $root]));
    }

    public function testRepositoryForNamesNothingWhereNeitherSaysWhereTheCodeLives(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-repository-' . uniqid('', true);
        mkdir($directory, 0777, true);
        $config = new DocGenConfig($directory, ['.'], [], [], 'build/docs', null, null, null);
        $root = new DiscoveredPackage(new ComposerManifest($directory, 'demo/app', '', [], [], [], [], []), false);
        $elsewhere = new DiscoveredPackage(new ComposerManifest('/tmp/other', 'demo/other', '', [], [], [], [], [], [], [], 'https://github.com/example/other'), false);

        self::assertNull((new ProjectAnalysis())->repositoryFor($config, [$root]));
        self::assertNull((new ProjectAnalysis())->repositoryFor($config, [$elsewhere]));
    }
}

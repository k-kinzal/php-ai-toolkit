<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis;

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
use Toolkit\DocGen\Analysis\Coverage\MethodCoverage;
use Toolkit\DocGen\Analysis\Internal\Coverage\CoverageReader;
use Toolkit\DocGen\Analysis\Internal\Layer\DeptracConfigReader;
use Toolkit\DocGen\Analysis\Internal\Layer\LayerAssigner;
use Toolkit\DocGen\Analysis\Internal\Package\PackageGraphBuilder;
use Toolkit\DocGen\Analysis\Layer\LayerCollector;
use Toolkit\DocGen\Analysis\Layer\LayerDefinition;
use Toolkit\DocGen\Analysis\Layer\LayerModel;
use Toolkit\DocGen\Analysis\Package\PackageGraph;
use Toolkit\DocGen\Analysis\ProjectAnalyzer;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCase as ReferenceTestCase;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Cache\ToolkitFingerprint;
use Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver;
use Toolkit\DocGen\Discovery\Internal\DocumentCollector;
use Toolkit\DocGen\Discovery\Internal\Filesystem\MarkdownFileFinder;
use Toolkit\DocGen\Discovery\Internal\Filesystem\SourceFileFinder;
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
use Toolkit\DocGen\Parallel\CpuCoreCounter;
use Toolkit\DocGen\Parallel\WorkerCount;
use Toolkit\DocGen\Parallel\WorkerPool;
use Toolkit\DocGen\Parallel\WorkScheduler;
use Toolkit\DocGen\Parse\Internal\AstParser;
use Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder;
use Toolkit\DocGen\Parse\Internal\Cache\SourceFileKey;
use Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader;
use Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Parse\Internal\ExprTextPrinter;
use Toolkit\DocGen\Parse\Internal\FileSymbolCollector;
use Toolkit\DocGen\Parse\Internal\NativeTypePrinter;
use Toolkit\DocGen\Parse\Internal\ParameterModifiers;
use Toolkit\DocGen\Parse\Internal\PhpParserBridge;
use Toolkit\DocGen\Parse\Internal\Reference\LocalTypeMap;
use Toolkit\DocGen\Parse\Internal\Reference\PropertyTypeScanner;
use Toolkit\DocGen\Parse\Internal\Reference\UsageCollector;
use Toolkit\DocGen\Parse\Internal\SymbolContext;
use Toolkit\DocGen\Parse\Internal\UseMapCollector;
use Toolkit\DocGen\Parse\ParsedProject;
use Toolkit\DocGen\Parse\ProjectSymbolCollector;
use Toolkit\DocGen\Parse\Reference\Usage;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Parse\Symbol\FileSymbols;
use Toolkit\DocGen\Parse\Symbol\MethodDoc;
use Toolkit\DocGen\Parse\Symbol\ParameterDoc;
use Toolkit\DocGen\Parse\Symbol\TypeSignature;
use Toolkit\DocGen\Report\RenderedSite;

/**
 * @uses \Toolkit\DocGen\Action\ProjectAnalysis
 * @uses \Toolkit\DocGen\Parse\Internal\AstParser
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\ComposerLockReader
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\ComposerManifestReader
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Analysis\Coverage\CoverageIndex
 * @uses \Toolkit\DocGen\Analysis\Internal\Coverage\CoverageReader
 * @uses \Toolkit\DocGen\Parallel\CpuCoreCounter
 * @uses \Toolkit\DocGen\Analysis\Internal\Layer\DeptracConfigReader
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\DevPackageResolver
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Action\Config\DocGenConfig
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver
 * @uses \Toolkit\DocGen\Discovery\Internal\DocumentCollector
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\Internal\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Analysis\Internal\Layer\LayerAssigner
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerCollector
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerDefinition
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerModel
 * @uses \Toolkit\DocGen\Parse\Internal\Reference\LocalTypeMap
 * @uses \Toolkit\DocGen\Discovery\Internal\Filesystem\MarkdownFileFinder
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Analysis\Coverage\MethodCoverage
 * @uses \Toolkit\DocGen\Parse\Symbol\MethodDoc
 * @uses \Toolkit\DocGen\Parse\Internal\NativeTypePrinter
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\PackageDiscovery
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Analysis\Internal\Package\PackageGraphBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ParameterDoc
 * @uses \Toolkit\DocGen\Parse\Internal\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\PhpParserBridge
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Parse\ProjectSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\Reference\PropertyTypeScanner
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCase
 * @uses \Toolkit\DocGen\Action\Config\RepositoryUrl
 * @uses \Toolkit\DocGen\Discovery\Internal\Filesystem\SourceFileFinder
 * @uses \Toolkit\DocGen\Parse\Internal\Cache\SourceFileKey
 * @uses \Toolkit\DocGen\Parse\Internal\SymbolContext
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Cache\ToolkitFingerprint
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Parse\Reference\Usage
 * @uses \Toolkit\DocGen\Parse\Internal\Reference\UsageCollector
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Parse\Internal\UseMapCollector
 * @uses \Toolkit\DocGen\Discovery\Internal\Package\VendorPackageLocator
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
 * @uses \Toolkit\DocGen\Discovery\SourceSelection
 * @uses \Toolkit\DocGen\Discovery\SourceFile
 * @uses \Toolkit\DocGen\Discovery\SourceSet
 * @uses \Toolkit\DocGen\Discovery\SourceDiscovery
 * @uses \Toolkit\DocGen\Parse\ParsedProject
 * @uses \Toolkit\DocGen\Analysis\ProjectAnalyzer
 * @uses \Toolkit\DocGen\Action\GenerateDocumentation
 * @uses \Toolkit\DocGen\Action\GenerationRequest
 * @uses \Toolkit\DocGen\Action\GenerationResult
 * @uses \Toolkit\DocGen\Report\RenderedSite
 * @covers \Toolkit\DocGen\Analysis\AnalysisOptions
 * @uses \Toolkit\DocGen\Discovery\Package\RepositoryAddress
 */
#[UsesClass(ProjectAnalysis::class)]
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

#[UsesClass(ProjectAnalyzer::class)]
#[UsesClass(GenerateDocumentation::class)]
#[UsesClass(GenerationRequest::class)]
#[UsesClass(GenerationResult::class)]
#[UsesClass(RenderedSite::class)]
#[CoversClass(AnalysisOptions::class)]
#[UsesClass(RepositoryAddress::class)]
final class AnalysisOptionsTest extends TestCase
{
    public function testAnalyzeLoadsLayersFromRootDeptracConfig(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-analyzer-' . bin2hex(random_bytes(4));
        mkdir($dir . '/src', 0777, true);
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
}
PHP);
        file_put_contents($dir . '/deptrac.yaml', <<<'YAML'
deptrac:
  layers:
    - name: Domain
      collectors:
        - type: className
          value: Greeter
  ruleset:
    Domain: []
YAML);
        $root = (string) realpath($dir);

        $model = (new ProjectAnalysis())->analyze(new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, null));

        $layers = $model->layers;

        self::assertNotNull($layers);
        self::assertCount(1, $layers->layers);
        self::assertSame('Domain', $layers->layers[0]->name);
        self::assertSame(['demo\greeter' => ['Domain']], $model->layerAssignments);
    }
}

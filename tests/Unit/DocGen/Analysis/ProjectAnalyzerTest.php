<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Coverage\CoverageIndex;
use Toolkit\DocGen\Analysis\Coverage\CoverageReader;
use Toolkit\DocGen\Analysis\Coverage\MethodCoverage;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Analysis\Document\DocumentCollector;
use Toolkit\DocGen\Analysis\Layer\DeptracConfigReader;
use Toolkit\DocGen\Analysis\Layer\LayerAssigner;
use Toolkit\DocGen\Analysis\Layer\LayerCollector;
use Toolkit\DocGen\Analysis\Layer\LayerDefinition;
use Toolkit\DocGen\Analysis\Layer\LayerModel;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\MethodDoc;
use Toolkit\DocGen\Analysis\Model\ParameterDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
use Toolkit\DocGen\Analysis\Parse\AstParser;
use Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder;
use Toolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use Toolkit\DocGen\Analysis\Parse\FileSymbolCollector;
use Toolkit\DocGen\Analysis\Parse\FileSymbols;
use Toolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use Toolkit\DocGen\Analysis\Parse\ParameterModifiers;
use Toolkit\DocGen\Analysis\Parse\PhpParserBridge;
use Toolkit\DocGen\Analysis\Parse\ProjectSymbolCollector;
use Toolkit\DocGen\Analysis\Parse\SymbolContext;
use Toolkit\DocGen\Analysis\Parse\UseMapCollector;
use Toolkit\DocGen\Analysis\ProjectAnalyzer;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\LocalTypeMap;
use Toolkit\DocGen\Analysis\Reference\PropertyTypeScanner;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCase as ReferenceTestCase;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\Usage;
use Toolkit\DocGen\Analysis\Reference\UsageCollector;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Cache\SourceFileKey;
use Toolkit\DocGen\Cache\ToolkitFingerprint;
use Toolkit\DocGen\Config\DocGenConfig;
use Toolkit\DocGen\Config\RepositoryUrl;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Filesystem\DocGenPathResolver;
use Toolkit\DocGen\Filesystem\MarkdownFileFinder;
use Toolkit\DocGen\Filesystem\SourceFileFinder;
use Toolkit\DocGen\Package\ComposerLockReader;
use Toolkit\DocGen\Package\ComposerManifest;
use Toolkit\DocGen\Package\ComposerManifestReader;
use Toolkit\DocGen\Package\DevPackageResolver;
use Toolkit\DocGen\Package\DiscoveredPackage;
use Toolkit\DocGen\Package\PackageDiscovery;
use Toolkit\DocGen\Package\PackageGraph;
use Toolkit\DocGen\Package\PackageGraphBuilder;
use Toolkit\DocGen\Package\VendorPackageLocator;
use Toolkit\DocGen\Parallel\CpuCoreCounter;
use Toolkit\DocGen\Parallel\WorkerCount;
use Toolkit\DocGen\Parallel\WorkerPool;
use Toolkit\DocGen\Parallel\WorkScheduler;

/**
 * @covers \Toolkit\DocGen\Analysis\ProjectAnalyzer
 * @uses \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Package\ComposerLockReader
 * @uses \Toolkit\DocGen\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Package\ComposerManifestReader
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Analysis\Coverage\CoverageIndex
 * @uses \Toolkit\DocGen\Analysis\Coverage\CoverageReader
 * @uses \Toolkit\DocGen\Parallel\CpuCoreCounter
 * @uses \Toolkit\DocGen\Analysis\Layer\DeptracConfigReader
 * @uses \Toolkit\DocGen\Package\DevPackageResolver
 * @uses \Toolkit\DocGen\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Config\DocGenConfig
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Filesystem\DocGenPathResolver
 * @uses \Toolkit\DocGen\Analysis\Document\DocumentCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbols
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerAssigner
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerCollector
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerDefinition
 * @uses \Toolkit\DocGen\Analysis\Layer\LayerModel
 * @uses \Toolkit\DocGen\Analysis\Reference\LocalTypeMap
 * @uses \Toolkit\DocGen\Filesystem\MarkdownFileFinder
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Analysis\Coverage\MethodCoverage
 * @uses \Toolkit\DocGen\Analysis\Model\MethodDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Package\PackageDiscovery
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Package\PackageGraphBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ParameterDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Analysis\Parse\ProjectSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Analysis\Reference\PropertyTypeScanner
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCase
 * @uses \Toolkit\DocGen\Config\RepositoryUrl
 * @uses \Toolkit\DocGen\Filesystem\SourceFileFinder
 * @uses \Toolkit\DocGen\Cache\SourceFileKey
 * @uses \Toolkit\DocGen\Analysis\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Cache\ToolkitFingerprint
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\Usage
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageCollector
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Analysis\Parse\UseMapCollector
 * @uses \Toolkit\DocGen\Package\VendorPackageLocator
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
 */
#[CoversClass(ProjectAnalyzer::class)]
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
final class ProjectAnalyzerTest extends TestCase
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

        $model = (new ProjectAnalyzer())->analyze(new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, null));

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

        $model = (new ProjectAnalyzer())->analyze(new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, null));

        self::assertCount(1, $model->warnings);
        self::assertStringContainsString('Failed to parse src/Broken.php', $model->warnings[0]);
        self::assertCount(1, $model->classLikes);
        self::assertSame('Demo\Valid', $model->classLikes[0]->fqcn);
    }

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

        $model = (new ProjectAnalyzer())->analyze(new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, null));

        $layers = $model->layers;

        self::assertNotNull($layers);
        self::assertCount(1, $layers->layers);
        self::assertSame('Domain', $layers->layers[0]->name);
        self::assertSame(['demo\greeter' => ['Domain']], $model->layerAssignments);
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

        $model = (new ProjectAnalyzer())->analyze(new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, 'coverage-xml'));

        $coverage = $model->coverage;

        self::assertNotNull($coverage);
        self::assertSame(['DemoTests\GreeterTest::testGreet'], $coverage->testsForRange('src/Greeter.php', 1, 100));
        $method = $coverage->methodAt('src/Greeter.php', 1, 100);
        self::assertNotNull($method);
        self::assertSame(1, $method->executable);
    }

    public function testLayerAssignmentsReturnsEmptyMapWithoutLayers(): void
    {
        self::assertSame([], (new ProjectAnalyzer())->layerAssignments(null, []));
    }

    public function testLayerAssignmentsMapsMatchingClassesToLayerNames(): void
    {
        $layers = new LayerModel([new LayerDefinition('Domain', [new LayerCollector('className', 'Greeter')])], []);
        $greeter = new ClassLikeDoc('Demo\Greeter', 'Greeter', 'Demo', 'class', 'demo/app', 'src/Greeter.php', 1, 5, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $mailer = new ClassLikeDoc('Demo\Mailer', 'Mailer', 'Demo', 'class', 'demo/app', 'src/Mailer.php', 1, 5, false, false, [], [], [], [], [], [], [], null, null, [], false);

        self::assertSame(['demo\greeter' => ['Domain']], (new ProjectAnalyzer())->layerAssignments($layers, [$greeter, $mailer]));
    }

    public function testLayerModelThrowsWhenConfiguredDeptracFileIsMissing(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-analyzer-' . bin2hex(random_bytes(4));
        $config = new DocGenConfig($dir, ['.'], [], [], 'build/docs', null, 'missing/deptrac.yaml', null);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Deptrac config not found: ' . $dir . '/missing/deptrac.yaml');

        (new ProjectAnalyzer())->layerModel($config);
    }

    public function testLayerModelReturnsNullWithoutDeptracConfiguration(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-analyzer-' . bin2hex(random_bytes(4));
        $config = new DocGenConfig($dir, ['.'], [], [], 'build/docs', null, null, null);

        self::assertNull((new ProjectAnalyzer())->layerModel($config));
    }

    public function testCoverageIndexReturnsNullWithoutConfiguredReport(): void
    {
        $config = new DocGenConfig('/tmp/demo', ['.'], [], [], 'build/docs', null, null, null);

        self::assertNull((new ProjectAnalyzer())->coverageIndex($config));
    }

    public function testCoverageIndexThrowsWhenReportDirectoryIsMissing(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-analyzer-' . bin2hex(random_bytes(4));
        $config = new DocGenConfig($dir, ['.'], [], [], 'build/docs', null, null, 'coverage-xml');

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Coverage report directory not found: ' . $dir . '/coverage-xml');

        (new ProjectAnalyzer())->coverageIndex($config);
    }

    public function testVendorWarningsReportsGlobThatMatchedNoPackage(): void
    {
        $config = new DocGenConfig('/tmp/demo', ['.'], ['vendor'], [], 'build/docs', null, null, null, ['dev-vendor']);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/demo', 'demo/app', '', [], [], [], [], []), false);

        $warnings = (new ProjectAnalyzer())->vendorWarnings($config, [$package]);

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
        $config = new DocGenConfig('/tmp/demo', ['.'], ['acme/*'], [], 'build/docs', null, null, null, ['phpunit/*']);
        $runtime = new DiscoveredPackage(new ComposerManifest('/tmp/demo/vendor/acme/lib', 'acme/lib', '', ['Acme\\' => ['src']], [], [], [], []), true);
        $dev = new DiscoveredPackage(new ComposerManifest('/tmp/demo/vendor/phpunit/phpunit', 'phpunit/phpunit', '', ['PHPUnit\\' => ['src']], [], [], [], []), true, true);

        self::assertSame([], (new ProjectAnalyzer())->vendorWarnings($config, [$runtime, $dev]));
    }

    public function testVendorWarningsReportsVendorPackageWithoutSources(): void
    {
        $config = new DocGenConfig('/tmp/demo', ['.'], ['phpstan/*'], [], 'build/docs', null, null, null);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/demo/vendor/phpstan/phpstan', 'phpstan/phpstan', '', [], [], [], [], []), true);

        $warnings = (new ProjectAnalyzer())->vendorWarnings($config, [$package]);

        self::assertCount(1, $warnings);
        self::assertSame(
            'Vendor package "phpstan/phpstan" declares no PSR-4 or classmap autoload source, so its classes cannot be documented or linked. Packages that autoload only "files" entries, such as a phar bootstrap, cannot be documented: drop "phpstan/phpstan" from the vendor globs.',
            $warnings[0],
        );
    }

    public function testVendorGlobWarningsIgnoresPackagesOfTheOtherDependencyKind(): void
    {
        $devPackage = new DiscoveredPackage(new ComposerManifest('/tmp/demo/vendor/phpunit/phpunit', 'phpunit/phpunit', '', ['PHPUnit\\' => ['src']], [], [], [], []), true, true);

        $warnings = (new ProjectAnalyzer())->vendorGlobWarnings(['phpunit/*'], [$devPackage], false);

        self::assertCount(1, $warnings);
        self::assertStringContainsString('documented no installed runtime vendor package', $warnings[0]);
        self::assertSame([], (new ProjectAnalyzer())->vendorGlobWarnings(['phpunit/*'], [$devPackage], true));
    }

    public function testVendorSourceWarningsIgnoresProjectPackagesAndDocumentedVendors(): void
    {
        $project = new DiscoveredPackage(new ComposerManifest('/tmp/demo', 'demo/app', '', [], [], [], [], []), false);
        $vendor = new DiscoveredPackage(new ComposerManifest('/tmp/demo/vendor/acme/lib', 'acme/lib', '', ['Acme\\' => ['src']], [], [], [], []), true);

        self::assertSame([], (new ProjectAnalyzer())->vendorSourceWarnings([$project, $vendor]));
    }

    public function testTitleForPrefersConfiguredTitle(): void
    {
        $config = new DocGenConfig('/tmp/demo', ['.'], [], [], 'build/docs', 'Custom Title', null, null);

        self::assertSame('Custom Title', (new ProjectAnalyzer())->titleFor($config, []));
    }

    public function testTitleForFallsBackToRootBasenameWithoutRootPackage(): void
    {
        $config = new DocGenConfig('/tmp/demo-docs', ['.'], [], [], 'build/docs', null, null, null);
        $vendorPackage = new DiscoveredPackage(new ComposerManifest('/tmp/other', 'vendor/lib', '', [], [], [], [], []), true);

        self::assertSame('demo-docs', (new ProjectAnalyzer())->titleFor($config, [$vendorPackage]));
    }

    public function testRepositoryForPrefersTheConfiguredAddress(): void
    {
        $config = new DocGenConfig('/tmp/demo', ['.'], [], [], 'build/docs', null, null, null, [], null, null, 'https://github.com/example/configured');
        $root = new DiscoveredPackage(new ComposerManifest('/tmp/demo', 'demo/app', '', [], [], [], [], [], [], [], 'https://github.com/example/declared'), false);

        self::assertSame('https://github.com/example/configured', (new ProjectAnalyzer())->repositoryFor($config, [$root]));
    }

    public function testRepositoryForReadsTheRootPackageWhenNothingIsConfigured(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-repository-' . uniqid('', true);
        mkdir($directory, 0777, true);
        $config = new DocGenConfig($directory, ['.'], [], [], 'build/docs', null, null, null);
        $vendor = new DiscoveredPackage(new ComposerManifest($directory, 'acme/lib', '', [], [], [], [], [], [], [], 'https://github.com/acme/lib'), true);
        $root = new DiscoveredPackage(new ComposerManifest($directory, 'demo/app', '', [], [], [], [], [], [], [], 'https://github.com/example/declared'), false);

        self::assertSame('https://github.com/example/declared', (new ProjectAnalyzer())->repositoryFor($config, [$vendor, $root]));
    }

    public function testRepositoryForNamesNothingWhereNeitherSaysWhereTheCodeLives(): void
    {
        $directory = sys_get_temp_dir() . '/docgen-repository-' . uniqid('', true);
        mkdir($directory, 0777, true);
        $config = new DocGenConfig($directory, ['.'], [], [], 'build/docs', null, null, null);
        $root = new DiscoveredPackage(new ComposerManifest($directory, 'demo/app', '', [], [], [], [], []), false);
        $elsewhere = new DiscoveredPackage(new ComposerManifest('/tmp/other', 'demo/other', '', [], [], [], [], [], [], [], 'https://github.com/example/other'), false);

        self::assertNull((new ProjectAnalyzer())->repositoryFor($config, [$root]));
        self::assertNull((new ProjectAnalyzer())->repositoryFor($config, [$elsewhere]));
    }
}

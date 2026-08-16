<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis;

use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageIndex;
use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageReader;
use PhpAiToolkit\DocGen\Analysis\Coverage\MethodCoverage;
use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Document\DocumentCollector;
use PhpAiToolkit\DocGen\Analysis\Layer\DeptracConfigReader;
use PhpAiToolkit\DocGen\Analysis\Layer\LayerAssigner;
use PhpAiToolkit\DocGen\Analysis\Layer\LayerCollector;
use PhpAiToolkit\DocGen\Analysis\Layer\LayerDefinition;
use PhpAiToolkit\DocGen\Analysis\Layer\LayerModel;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\MethodDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ParameterDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpAiToolkit\DocGen\Analysis\Parse\AstParser;
use PhpAiToolkit\DocGen\Analysis\Parse\ClassLikeBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ConstantBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\EnumCaseBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\FileSymbolCollector;
use PhpAiToolkit\DocGen\Analysis\Parse\FileSymbols;
use PhpAiToolkit\DocGen\Analysis\Parse\FunctionBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\MethodBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\ParameterBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ParameterModifiers;
use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\DocGen\Analysis\Parse\PropertyBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\SymbolContext;
use PhpAiToolkit\DocGen\Analysis\Parse\UseMapCollector;
use PhpAiToolkit\DocGen\Analysis\ProjectAnalyzer;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\LocalTypeMap;
use PhpAiToolkit\DocGen\Analysis\Reference\PropertyTypeScanner;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCase as ReferenceTestCase;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\Usage;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageCollector;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Config\DocGenConfig;
use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver;
use PhpAiToolkit\DocGen\Filesystem\MarkdownFileFinder;
use PhpAiToolkit\DocGen\Filesystem\SourceFileFinder;
use PhpAiToolkit\DocGen\Package\ComposerLockReader;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\ComposerManifestReader;
use PhpAiToolkit\DocGen\Package\DevPackageResolver;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Package\PackageDiscovery;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Package\PackageGraphBuilder;
use PhpAiToolkit\DocGen\Package\VendorPackageLocator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(PropertyTypeScanner::class)]
#[UsesClass(SourceFileFinder::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(ReferenceTestCase::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(Usage::class)]
#[UsesClass(UsageCollector::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UseMapCollector::class)]
#[UsesClass(VendorPackageLocator::class)]
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
}

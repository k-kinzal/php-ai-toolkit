<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageReader;
use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use PhpAiToolkit\DocGen\Analysis\Layer\DeptracConfigReader;
use PhpAiToolkit\DocGen\Analysis\Layer\LayerAssigner;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeKind;
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
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageCollector;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Cli\DocGenConfigOverrides;
use PhpAiToolkit\DocGen\Cli\DocGenConfigPathResolver;
use PhpAiToolkit\DocGen\Cli\DocGenGenerationRunner;
use PhpAiToolkit\DocGen\Cli\DocGenMemoryLimit;
use PhpAiToolkit\DocGen\Cli\DocGenOutputWriter;
use PhpAiToolkit\DocGen\Cli\DocGenPreviewServer;
use PhpAiToolkit\DocGen\Config\ConfigLoader;
use PhpAiToolkit\DocGen\Config\ConfigScalarReader;
use PhpAiToolkit\DocGen\Config\ConfigStringListReader;
use PhpAiToolkit\DocGen\Config\DocGenConfig;
use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver;
use PhpAiToolkit\DocGen\Filesystem\SiteFileWriter;
use PhpAiToolkit\DocGen\Filesystem\SourceFileFinder;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\ComposerManifestReader;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Package\PackageDiscovery;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Package\PackageGraphBuilder;
use PhpAiToolkit\DocGen\Package\VendorPackageLocator;
use PhpAiToolkit\DocGen\Render\AssetPublisher;
use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownInline;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\Page\BreadcrumbHtml;
use PhpAiToolkit\DocGen\Render\Page\ClassLikePage;
use PhpAiToolkit\DocGen\Render\Page\DocTextHtml;
use PhpAiToolkit\DocGen\Render\Page\ExampleHtml;
use PhpAiToolkit\DocGen\Render\Page\FunctionPage;
use PhpAiToolkit\DocGen\Render\Page\GraphSvg;
use PhpAiToolkit\DocGen\Render\Page\IndexPage;
use PhpAiToolkit\DocGen\Render\Page\MemberHtml;
use PhpAiToolkit\DocGen\Render\Page\NamespacePage;
use PhpAiToolkit\DocGen\Render\Page\PackagePage;
use PhpAiToolkit\DocGen\Render\Page\RelationsHtml;
use PhpAiToolkit\DocGen\Render\Page\SidebarHtml;
use PhpAiToolkit\DocGen\Render\Page\SignatureHtml;
use PhpAiToolkit\DocGen\Render\Page\SourcePage;
use PhpAiToolkit\DocGen\Render\Page\UsageListHtml;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\SearchIndexBuilder;
use PhpAiToolkit\DocGen\Render\SiteRenderer;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PhpAiToolkit\DocGen\Render\TypeRenderContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocGenGenerationRunner::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(BreadcrumbHtml::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikeKind::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ComposerManifestReader::class)]
#[UsesClass(ConfigLoader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(CoverageReader::class)]
#[UsesClass(DeptracConfigReader::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocGenConfig::class)]
#[UsesClass(DocGenConfigOverrides::class)]
#[UsesClass(DocGenConfigPathResolver::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(DocGenMemoryLimit::class)]
#[UsesClass(DocGenOutputWriter::class)]
#[UsesClass(DocGenPathResolver::class)]
#[UsesClass(DocGenPreviewServer::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(ExampleHtml::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(FunctionPage::class)]
#[UsesClass(GraphSvg::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(IndexPage::class)]
#[UsesClass(LayerAssigner::class)]
#[UsesClass(LocalTypeMap::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(MemberHtml::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(NamespacePage::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(PackageDiscovery::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PackageGraphBuilder::class)]
#[UsesClass(PackagePage::class)]
#[UsesClass(PageChrome::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpHighlighter::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(ProjectAnalyzer::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(RelationsHtml::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SignatureHtml::class)]
#[UsesClass(SiteFileWriter::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SourceFileFinder::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeRenderContext::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageCollector::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UsageListHtml::class)]
#[UsesClass(UseMapCollector::class)]
#[UsesClass(VendorPackageLocator::class)]
final class DocGenGenerationRunnerTest extends TestCase
{
    public function testRunGeneratesSiteWithZeroConfigDefaults(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/composer.json', <<<'JSON'
{
    "name": "acme/demo",
    "autoload": {"psr-4": {"Acme\\Demo\\": "src/"}}
}
JSON);
        file_put_contents($dir . '/src/Greeter.php', <<<'PHP'
<?php

namespace Acme\Demo;

final class Greeter
{
    public function greet(string $name): string
    {
        return 'Hello ' . $name;
    }
}
PHP);

        $output = '';
        $errors = '';
        $runner = new DocGenGenerationRunner($dir, null, null, null, new DocGenOutputWriter(
            static function (string $message) use (&$output): void {
                $output .= $message;
            },
            static function (string $message) use (&$errors): void {
                $errors .= $message;
            },
        ));

        self::assertSame(0, $runner->run(['config' => null, 'output' => null, 'vendor' => null, 'vendorDev' => null, 'coverage' => null, 'serve' => null, 'memoryLimit' => null, 'help' => false, 'version' => false]));
        self::assertStringContainsString('Generated', $output);
        self::assertStringContainsString('build/docs', $output);
        self::assertSame('', $errors);
        self::assertFileExists($dir . '/build/docs/index.html');
    }

    public function testRunAppliesRequestedMemoryLimitAndWarnsAboutUnmatchedVendorGlob(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/composer.json', <<<'JSON'
{
    "name": "acme/demo",
    "autoload": {"psr-4": {"Acme\\Demo\\": "src/"}}
}
JSON);
        file_put_contents($dir . '/src/Greeter.php', <<<'PHP'
<?php

namespace Acme\Demo;

final class Greeter
{
    public function greet(string $name): string
    {
        return 'Hello ' . $name;
    }
}
PHP);

        $errors = '';
        $runner = new DocGenGenerationRunner($dir, null, null, null, new DocGenOutputWriter(
            null,
            static function (string $message) use (&$errors): void {
                $errors .= $message;
            },
        ));

        $previous = ini_get('memory_limit');
        $exitCode = $runner->run(['config' => null, 'output' => null, 'vendor' => ['vendor'], 'vendorDev' => null, 'coverage' => null, 'serve' => null, 'memoryLimit' => DocGenMemoryLimit::FLOOR, 'help' => false, 'version' => false]);
        $applied = ini_get('memory_limit');
        ini_set('memory_limit', $previous);

        self::assertSame(0, $exitCode);
        self::assertSame(DocGenMemoryLimit::FLOOR, $applied);
        self::assertStringContainsString('Warning: Vendor glob "vendor" documented no installed runtime vendor package.', $errors);
    }

    public function testRunReportsMissingExplicitConfig(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $errors = '';
        $runner = new DocGenGenerationRunner($dir, null, null, null, new DocGenOutputWriter(
            null,
            static function (string $message) use (&$errors): void {
                $errors .= $message;
            },
        ));

        self::assertSame(2, $runner->run(['config' => 'missing.yaml', 'output' => null, 'vendor' => null, 'vendorDev' => null, 'coverage' => null, 'serve' => null, 'memoryLimit' => null, 'help' => false, 'version' => false]));
        self::assertStringContainsString('DocGen error: DocGen config not found:', $errors);
        self::assertStringContainsString($dir . '/missing.yaml', $errors);
    }

    public function testRunHonorsConfiguredOutputDirectory(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/composer.json', <<<'JSON'
{
    "name": "acme/demo",
    "autoload": {"psr-4": {"Acme\\Demo\\": "src/"}}
}
JSON);
        file_put_contents($dir . '/src/Greeter.php', <<<'PHP'
<?php

namespace Acme\Demo;

final class Greeter
{
    public function greet(string $name): string
    {
        return 'Hello ' . $name;
    }
}
PHP);
        file_put_contents($dir . '/doc.yaml', <<<'YAML'
output: public/site
YAML);

        $output = '';
        $runner = new DocGenGenerationRunner($dir, null, null, null, new DocGenOutputWriter(
            static function (string $message) use (&$output): void {
                $output .= $message;
            },
        ));

        self::assertSame(0, $runner->run(['config' => null, 'output' => null, 'vendor' => null, 'vendorDev' => null, 'coverage' => null, 'serve' => null, 'memoryLimit' => null, 'help' => false, 'version' => false]));
        self::assertStringContainsString('public/site', $output);
        self::assertFileExists($dir . '/public/site/index.html');
    }

    public function testRunLaunchesPreviewServerForServeOption(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/composer.json', <<<'JSON'
{
    "name": "acme/demo",
    "autoload": {"psr-4": {"Acme\\Demo\\": "src/"}}
}
JSON);
        file_put_contents($dir . '/src/Greeter.php', <<<'PHP'
<?php

namespace Acme\Demo;

final class Greeter
{
    public function greet(string $name): string
    {
        return 'Hello ' . $name;
    }
}
PHP);

        $output = '';
        $command = '';
        $runner = new DocGenGenerationRunner(
            $dir,
            null,
            null,
            null,
            new DocGenOutputWriter(static function (string $message) use (&$output): void {
                $output .= $message;
            }),
            previewServer: new DocGenPreviewServer(static function (string $launched) use (&$command): int {
                $command = $launched;

                return 0;
            }),
        );

        self::assertSame(0, $runner->run(['config' => null, 'output' => null, 'vendor' => null, 'vendorDev' => null, 'coverage' => null, 'serve' => '127.0.0.1:8123', 'memoryLimit' => null, 'help' => false, 'version' => false]));
        self::assertStringContainsString('Serving documentation at http://127.0.0.1:8123', $output);
        self::assertStringContainsString(' -S ', $command);
        self::assertStringContainsString('127.0.0.1:8123', $command);
        self::assertStringContainsString($dir . '/build/docs', $command);
    }

    public function testRunReportsMissingComposerPackages(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $errors = '';
        $runner = new DocGenGenerationRunner($dir, null, null, null, new DocGenOutputWriter(
            null,
            static function (string $message) use (&$errors): void {
                $errors .= $message;
            },
        ));

        self::assertSame(2, $runner->run(['config' => null, 'output' => null, 'vendor' => null, 'vendorDev' => null, 'coverage' => null, 'serve' => null, 'memoryLimit' => null, 'help' => false, 'version' => false]));
        self::assertStringContainsString('DocGen error: No composer packages found.', $errors);
    }

    public function testLoadConfigBuildsDefaultsWithoutConfigFile(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $config = (new DocGenGenerationRunner($dir))->loadConfig(null);

        self::assertSame(realpath($dir), $config->root);
        self::assertSame(['.', 'packages/*'], $config->packages);
        self::assertSame([], $config->vendor);
        self::assertSame([], $config->vendorDev);
        self::assertSame([], $config->exclude);
        self::assertSame('build/docs', $config->output);
        self::assertNull($config->title);
        self::assertNull($config->deptrac);
        self::assertNull($config->coverage);
    }

    public function testLoadConfigReadsProjectDocYamlWhenPresent(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/doc.yaml', <<<'YAML'
output: public/site
title: Demo Docs
YAML);

        $config = (new DocGenGenerationRunner($dir))->loadConfig(null);

        self::assertSame(realpath($dir), $config->root);
        self::assertSame('public/site', $config->output);
        self::assertSame('Demo Docs', $config->title);
    }

    public function testLoadConfigResolvesExplicitRelativePath(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir . '/conf', 0777, true);
        file_put_contents($dir . '/conf/doc.yaml', <<<'YAML'
title: Custom
YAML);

        $config = (new DocGenGenerationRunner($dir))->loadConfig('conf/doc.yaml');

        self::assertSame(realpath($dir . '/conf'), $config->root);
        self::assertSame('Custom', $config->title);
    }

    public function testLoadConfigRejectsMissingExplicitConfig(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-runner-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('DocGen config not found: ' . $dir . '/missing.yaml');

        (new DocGenGenerationRunner($dir))->loadConfig('missing.yaml');
    }
}

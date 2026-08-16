<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse;

use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
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
use PhpAiToolkit\DocGen\Analysis\Parse\ProjectSymbolCollector;
use PhpAiToolkit\DocGen\Analysis\Parse\PropertyBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\SymbolContext;
use PhpAiToolkit\DocGen\Analysis\Parse\UseMapCollector;
use PhpAiToolkit\DocGen\Analysis\Reference\LocalTypeMap;
use PhpAiToolkit\DocGen\Analysis\Reference\PropertyTypeScanner;
use PhpAiToolkit\DocGen\Analysis\Reference\Usage;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageCollector;
use PhpAiToolkit\DocGen\Config\DocGenConfig;
use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver;
use PhpAiToolkit\DocGen\Filesystem\SourceFileFinder;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Parallel\CpuCoreCounter;
use PhpAiToolkit\DocGen\Parallel\ForkSupport;
use PhpAiToolkit\DocGen\Parallel\WorkerCount;
use PhpAiToolkit\DocGen\Parallel\WorkerPool;
use PhpAiToolkit\DocGen\Parallel\WorkScheduler;
use PhpParser\NodeTraverser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProjectSymbolCollector::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(CpuCoreCounter::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocGenConfig::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(DocGenPathResolver::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(ForkSupport::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(LocalTypeMap::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(PropertyTypeScanner::class)]
#[UsesClass(SourceFileFinder::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(Usage::class)]
#[UsesClass(UsageCollector::class)]
#[UsesClass(UseMapCollector::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
#[UsesClass(WorkScheduler::class)]
final class ProjectSymbolCollectorTest extends TestCase
{
    public function testCollectParsesPackageSourcesIntoSymbolLists(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-analyzer-' . bin2hex(random_bytes(4));
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/src/Greeter.php', <<<'PHP'
<?php

namespace Demo;

class Greeter
{
}
PHP);
        $root = (string) realpath($dir);
        $manifest = new ComposerManifest($root, 'demo/app', '', ['Demo\\' => ['src']], [], [], [], []);
        $config = new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, null);

        $collected = (new ProjectSymbolCollector())->collect($config, [new DiscoveredPackage($manifest, false)]);

        self::assertCount(1, $collected['classLikes']);
        self::assertSame('Demo\Greeter', $collected['classLikes'][0]->fqcn);
        self::assertSame([], $collected['functions']);
        self::assertSame([], $collected['warnings']);
    }



    public function testCollectFileReturnsSymbolsAndRecordsUsages(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-analyzer-' . bin2hex(random_bytes(4));
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/src/App.php', <<<'PHP'
<?php

namespace Demo;

class App
{
    public function run(): void
    {
        new \Demo\Widget();
    }
}
PHP);
        $root = (string) realpath($dir);
        $manifest = new ComposerManifest($root, 'demo/app', '', ['Demo\\' => ['src']], [], [], [], []);
        $config = new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, null);
        $collector = new UsageCollector();
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);

        $result = (new ProjectSymbolCollector())->collectFile($config, new DiscoveredPackage($manifest, false), ['directory' => $root . '/src', 'isDev' => false], $root . '/src/App.php', $collector, $traverser);

        self::assertInstanceOf(FileSymbols::class, $result);
        self::assertCount(1, $result->classLikes);
        self::assertSame('Demo\App', $result->classLikes[0]->fqcn);
        $usages = $collector->usages();
        self::assertCount(1, $usages);
        self::assertSame('Demo\Widget', $usages[0]->targetFqcn);
        self::assertSame('src/App.php', $usages[0]->file);
    }



    public function testCollectFileReturnsWarningForUnparsableFile(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-analyzer-' . bin2hex(random_bytes(4));
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/src/Broken.php', '<?php class {');
        $root = (string) realpath($dir);
        $manifest = new ComposerManifest($root, 'demo/app', '', ['Demo\\' => ['src']], [], [], [], []);
        $config = new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, null);
        $collector = new UsageCollector();
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);

        $result = (new ProjectSymbolCollector())->collectFile($config, new DiscoveredPackage($manifest, false), ['directory' => $root . '/src', 'isDev' => false], $root . '/src/Broken.php', $collector, $traverser);

        self::assertIsString($result);
        self::assertStringContainsString('Failed to parse src/Broken.php', $result);
    }



    public function testSourceDirectoriesListsAutoloadAndDevAutoloadDirectories(): void
    {
        $manifest = new ComposerManifest('/tmp/demo', 'demo/app', '', ['Demo\\' => ['src']], ['DemoTests\\' => ['tests']], [], [], []);

        $sources = (new ProjectSymbolCollector())->sourceDirectories(new DiscoveredPackage($manifest, false));

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

        $sources = (new ProjectSymbolCollector())->sourceDirectories(new DiscoveredPackage($manifest, false));

        self::assertSame([
            ['directory' => $dir . '/src', 'isDev' => false],
            ['directory' => $dir . '/lib/legacy', 'isDev' => false],
            ['directory' => $dir . '/tests/Fixture', 'isDev' => true],
        ], $sources);
    }



    public function testSourceDirectoriesMapsEmptyPsr4PathToPackageRoot(): void
    {
        $manifest = new ComposerManifest('/tmp/demo/vendor/symfony/yaml', 'symfony/yaml', '', ['Symfony\\Component\\Yaml\\' => ['']], [], [], [], []);

        $sources = (new ProjectSymbolCollector())->sourceDirectories(new DiscoveredPackage($manifest, true));

        self::assertSame([['directory' => '/tmp/demo/vendor/symfony/yaml', 'isDev' => false]], $sources);
    }



    public function testSourceDirectoriesReturnsNothingForPharOnlyPackage(): void
    {
        $manifest = new ComposerManifest('/tmp/demo/vendor/phpstan/phpstan', 'phpstan/phpstan', '', [], [], [], [], []);

        self::assertSame([], (new ProjectSymbolCollector())->sourceDirectories(new DiscoveredPackage($manifest, true)));
    }

    public function testSourceFilesListsEveryPackageSourceOnceInDiscoveryOrder(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-collector-' . bin2hex(random_bytes(4));
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/src/Alpha.php', "<?php\n\nnamespace Demo;\n\nclass Alpha\n{\n}\n");
        file_put_contents($dir . '/src/Beta.php', "<?php\n\nnamespace Demo;\n\nclass Beta\n{\n}\n");
        $root = (string) realpath($dir);
        $manifest = new ComposerManifest($root, 'demo/app', '', ['Demo\\' => ['src']], [], [], [], [], ['src']);
        $config = new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, null);

        $files = (new ProjectSymbolCollector())->sourceFiles($config, [new DiscoveredPackage($manifest, false)]);

        self::assertCount(2, $files);
        self::assertSame($root . '/src/Alpha.php', $files[0]['file']);
        self::assertSame($root . '/src/Beta.php', $files[1]['file']);
        self::assertSame('demo/app', $files[0]['package']->manifest->name);
        self::assertFalse($files[0]['source']['isDev']);
    }

    public function testParseJobParsesEveryFileOfTheJobAndKeepsTheUsagesItFound(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-collector-' . bin2hex(random_bytes(4));
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/src/Alpha.php', "<?php\n\nnamespace Demo;\n\nclass Alpha\n{\n    public function run(Beta \$beta): void\n    {\n    }\n}\n");
        file_put_contents($dir . '/src/Beta.php', "<?php\n\nnamespace Demo;\n\nclass Beta\n{\n}\n");
        $root = (string) realpath($dir);
        $manifest = new ComposerManifest($root, 'demo/app', '', ['Demo\\' => ['src']], [], [], [], []);
        $config = new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, null);
        $collector = new ProjectSymbolCollector();

        $parsed = $collector->parseJob($config, $collector->sourceFiles($config, [new DiscoveredPackage($manifest, false)]));

        self::assertCount(2, $parsed['symbols']);
        self::assertInstanceOf(FileSymbols::class, $parsed['symbols'][0]);
        self::assertSame('Demo\Alpha', $parsed['symbols'][0]->classLikes[0]->fqcn);
        self::assertNotSame([], $parsed['usages']);
        self::assertSame('Demo\Beta', $parsed['usages'][0]->targetFqcn);
    }

    public function testMergedJoinsJobResultsAndDropsSymbolsAlreadySeen(): void
    {
        $alpha = new ClassLikeDoc('Demo\Alpha', 'Alpha', 'Demo', 'class', 'demo/app', 'src/Alpha.php', 1, 4, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $again = new ClassLikeDoc('demo\alpha', 'Alpha', 'Demo', 'class', 'demo/app', 'vendor/Alpha.php', 1, 4, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $beta = new ClassLikeDoc('Demo\Beta', 'Beta', 'Demo', 'class', 'demo/app', 'src/Beta.php', 1, 4, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $usage = new Usage('Demo\Beta', null, 'type', 'Demo\Alpha', 'run', 'src/Alpha.php', 7, false);

        $merged = (new ProjectSymbolCollector())->merged([
            ['symbols' => [new FileSymbols([$alpha], []), 'Skipped unreadable file: src/Broken.php'], 'usages' => [$usage]],
            ['symbols' => [new FileSymbols([$again, $beta], [])], 'usages' => []],
        ]);

        self::assertSame(['Demo\Alpha', 'Demo\Beta'], [$merged['classLikes'][0]->fqcn, $merged['classLikes'][1]->fqcn]);
        self::assertSame(['Skipped unreadable file: src/Broken.php'], $merged['warnings']);
        self::assertSame([$usage], $merged['usages']);
        self::assertSame([], $merged['functions']);
    }

    public function testParsedRejectsAWorkerResultThatIsNotAParseResult(): void
    {
        $collector = new ProjectSymbolCollector();
        $symbols = new FileSymbols([], []);

        self::assertSame(['symbols' => [$symbols], 'usages' => []], $collector->parsed(['symbols' => [$symbols], 'usages' => []]));

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('A documentation worker reported no parsed sources.');

        $collector->parsed('not a parse result');
    }
}

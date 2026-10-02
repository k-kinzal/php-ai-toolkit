<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
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
use Toolkit\DocGen\Analysis\Reference\LocalTypeMap;
use Toolkit\DocGen\Analysis\Reference\PropertyTypeScanner;
use Toolkit\DocGen\Analysis\Reference\Usage;
use Toolkit\DocGen\Analysis\Reference\UsageCollector;
use Toolkit\DocGen\Cache\SourceFileKey;
use Toolkit\DocGen\Cache\ToolkitFingerprint;
use Toolkit\DocGen\Config\DocGenConfig;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Filesystem\DocGenPathResolver;
use Toolkit\DocGen\Filesystem\SourceFileFinder;
use Toolkit\DocGen\Package\ComposerManifest;
use Toolkit\DocGen\Package\DiscoveredPackage;
use Toolkit\DocGen\Parallel\CpuCoreCounter;
use Toolkit\DocGen\Parallel\ForkSupport;
use Toolkit\DocGen\Parallel\WorkerCount;
use Toolkit\DocGen\Parallel\WorkerPool;
use Toolkit\DocGen\Parallel\WorkScheduler;

/**
 * @covers \Toolkit\DocGen\Analysis\Parse\ProjectSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Parallel\CpuCoreCounter
 * @uses \Toolkit\DocGen\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Config\DocGenConfig
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Filesystem\DocGenPathResolver
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbols
 * @uses \Toolkit\DocGen\Parallel\ForkSupport
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Analysis\Reference\LocalTypeMap
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\MethodDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ParameterDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Analysis\Reference\PropertyTypeScanner
 * @uses \Toolkit\DocGen\Filesystem\SourceFileFinder
 * @uses \Toolkit\DocGen\Cache\SourceFileKey
 * @uses \Toolkit\DocGen\Analysis\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Cache\ToolkitFingerprint
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\Usage
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\UseMapCollector
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
 */
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
#[UsesClass(MethodDoc::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(PropertyTypeScanner::class)]
#[UsesClass(SourceFileFinder::class)]
#[UsesClass(SourceFileKey::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(ToolkitFingerprint::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(Usage::class)]
#[UsesClass(UsageCollector::class)]
#[UsesClass(UseMapCollector::class)]
#[UsesClass(WorkScheduler::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
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
        $result = (new ProjectSymbolCollector())->collectFile($config, new DiscoveredPackage($manifest, false), ['directory' => $root . '/src', 'isDev' => false], $root . '/src/App.php', 'fingerprint');
        $symbols = $result['symbols'];

        self::assertFalse($result['cached']);
        self::assertInstanceOf(FileSymbols::class, $symbols);
        self::assertCount(1, $symbols->classLikes);
        self::assertSame('Demo\App', $symbols->classLikes[0]->fqcn);
        self::assertCount(1, $result['usages']);
        self::assertSame('Demo\Widget', $result['usages'][0]->targetFqcn);
        self::assertSame('src/App.php', $result['usages'][0]->file);
    }



    public function testCollectFileReturnsWarningForUnparsableFile(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-analyzer-' . bin2hex(random_bytes(4));
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/src/Broken.php', '<?php class {');
        $root = (string) realpath($dir);
        $manifest = new ComposerManifest($root, 'demo/app', '', ['Demo\\' => ['src']], [], [], [], []);
        $config = new DocGenConfig($root, ['.'], [], [], 'build/docs', null, null, null);
        $result = (new ProjectSymbolCollector())->collectFile($config, new DiscoveredPackage($manifest, false), ['directory' => $root . '/src', 'isDev' => false], $root . '/src/Broken.php', 'fingerprint');

        self::assertIsString($result['symbols']);
        self::assertStringContainsString('Failed to parse src/Broken.php', $result['symbols']);
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

        $parsed = $collector->parseJob($config, $collector->sourceFiles($config, [new DiscoveredPackage($manifest, false)]), 'fingerprint');
        $symbols = $parsed[0]['symbols'];

        self::assertCount(2, $parsed);
        self::assertInstanceOf(FileSymbols::class, $symbols);
        self::assertSame('Demo\Alpha', $symbols->classLikes[0]->fqcn);
        self::assertNotSame([], $parsed[0]['usages']);
        self::assertSame('Demo\Beta', $parsed[0]['usages'][0]->targetFqcn);
    }

    public function testMergedJoinsJobResultsAndDropsSymbolsAlreadySeen(): void
    {
        $alpha = new ClassLikeDoc('Demo\Alpha', 'Alpha', 'Demo', 'class', 'demo/app', 'src/Alpha.php', 1, 4, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $again = new ClassLikeDoc('demo\alpha', 'Alpha', 'Demo', 'class', 'demo/app', 'vendor/Alpha.php', 1, 4, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $beta = new ClassLikeDoc('Demo\Beta', 'Beta', 'Demo', 'class', 'demo/app', 'src/Beta.php', 1, 4, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $usage = new Usage('Demo\Beta', null, 'type', 'Demo\Alpha', 'run', 'src/Alpha.php', 7, false);

        $merged = (new ProjectSymbolCollector())->merged([
            [
                ['cached' => false, 'symbols' => new FileSymbols([$alpha], []), 'usages' => [$usage]],
                ['cached' => false, 'symbols' => 'Skipped unreadable file: src/Broken.php', 'usages' => []],
            ],
            [['cached' => true, 'symbols' => new FileSymbols([$again, $beta], []), 'usages' => []]],
        ]);

        self::assertSame(['Demo\Alpha', 'Demo\Beta'], [$merged['classLikes'][0]->fqcn, $merged['classLikes'][1]->fqcn]);
        self::assertSame(['Skipped unreadable file: src/Broken.php'], $merged['warnings']);
        self::assertSame([$usage], $merged['usages']);
        self::assertSame([], $merged['functions']);
    }

    public function testParsedRejectsAWorkerResultThatIsNotAParseResult(): void
    {
        $collector = new ProjectSymbolCollector();
        $file = ['cached' => false, 'symbols' => new FileSymbols([], []), 'usages' => []];

        self::assertSame([$file], $collector->parsed([$file]));

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('A documentation worker reported no parsed sources.');

        $collector->parsed('not a parse result');
    }

    public function testParsedFileRejectsAFileThatIsNotAParsedOne(): void
    {
        $collector = new ProjectSymbolCollector();

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('A documentation worker reported an unreadable symbol reference.');

        $collector->parsedFile(['cached' => false, 'symbols' => 'a warning', 'usages' => ['not a usage']]);
    }

    public function testParsedFileRejectsAFileWithoutSymbols(): void
    {
        $collector = new ProjectSymbolCollector();

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('A documentation worker reported an unreadable source file.');

        $collector->parsedFile(['cached' => false, 'symbols' => 42, 'usages' => []]);
    }

    public function testParseFileTurnsSourceIntoSymbolsAndUsages(): void
    {
        $parsed = (new ProjectSymbolCollector())->parseFile(
            "<?php\n\nnamespace Demo;\n\nclass Alpha\n{\n    public function run(Beta \$beta): void\n    {\n    }\n}\n",
            'src/Alpha.php',
            'demo/app',
            false,
        );
        $symbols = $parsed['symbols'];

        self::assertInstanceOf(FileSymbols::class, $symbols);
        self::assertSame('Demo\\Alpha', $symbols->classLikes[0]->fqcn);
        self::assertSame('Demo\\Beta', $parsed['usages'][0]->targetFqcn);
        self::assertSame('src/Alpha.php', $parsed['usages'][0]->file);
    }

    public function testParseFileReportsWhatItCouldNotParse(): void
    {
        $parsed = (new ProjectSymbolCollector())->parseFile('<?php class {', 'src/Broken.php', 'demo/app', false);

        self::assertIsString($parsed['symbols']);
        self::assertStringContainsString('Failed to parse src/Broken.php', $parsed['symbols']);
        self::assertSame([], $parsed['usages']);
    }
}

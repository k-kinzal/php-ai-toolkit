<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Discovery;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Action\Config\DocGenConfig;
use Toolkit\DocGen\Cache\ToolkitFingerprint;
use Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver;
use Toolkit\DocGen\Discovery\Internal\Filesystem\SourceFileFinder;
use Toolkit\DocGen\Discovery\Package\ComposerManifest;
use Toolkit\DocGen\Discovery\Package\DiscoveredPackage;
use Toolkit\DocGen\Discovery\SourceFile;
use Toolkit\DocGen\Discovery\SourceSet;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Parallel\CpuCoreCounter;
use Toolkit\DocGen\Parallel\ForkSupport;
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

/**
 * @uses \Toolkit\DocGen\Parse\ProjectSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Internal\AstParser
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Parallel\CpuCoreCounter
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Action\Config\DocGenConfig
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\Internal\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parallel\ForkSupport
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\Reference\LocalTypeMap
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\MethodDoc
 * @uses \Toolkit\DocGen\Parse\Internal\NativeTypePrinter
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ParameterDoc
 * @uses \Toolkit\DocGen\Parse\Internal\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\PhpParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\Reference\PropertyTypeScanner
 * @uses \Toolkit\DocGen\Discovery\Internal\Filesystem\SourceFileFinder
 * @uses \Toolkit\DocGen\Parse\Internal\Cache\SourceFileKey
 * @uses \Toolkit\DocGen\Parse\Internal\SymbolContext
 * @uses \Toolkit\DocGen\Cache\ToolkitFingerprint
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Parse\Reference\Usage
 * @uses \Toolkit\DocGen\Parse\Internal\Reference\UsageCollector
 * @uses \Toolkit\DocGen\Parse\Internal\UseMapCollector
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
 */
/**
 * @uses \Toolkit\DocGen\Parse\ProjectSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Internal\AstParser
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Parallel\CpuCoreCounter
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Action\Config\DocGenConfig
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Discovery\Filesystem\DocGenPathResolver
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\Internal\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parallel\ForkSupport
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\Reference\LocalTypeMap
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\MethodDoc
 * @uses \Toolkit\DocGen\Parse\Internal\NativeTypePrinter
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ParameterDoc
 * @uses \Toolkit\DocGen\Parse\Internal\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\PhpParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\Reference\PropertyTypeScanner
 * @uses \Toolkit\DocGen\Discovery\Internal\Filesystem\SourceFileFinder
 * @uses \Toolkit\DocGen\Parse\Internal\Cache\SourceFileKey
 * @uses \Toolkit\DocGen\Parse\Internal\SymbolContext
 * @uses \Toolkit\DocGen\Cache\ToolkitFingerprint
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Parse\Reference\Usage
 * @uses \Toolkit\DocGen\Parse\Internal\Reference\UsageCollector
 * @uses \Toolkit\DocGen\Parse\Internal\UseMapCollector
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
 * @covers \Toolkit\DocGen\Discovery\SourceFile
 * @uses \Toolkit\DocGen\Discovery\SourceSet
 * @uses \Toolkit\DocGen\Parse\ParsedProject
 */
#[UsesClass(ProjectSymbolCollector::class)]
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
#[CoversClass(SourceFile::class)]
#[UsesClass(SourceSet::class)]
#[UsesClass(ParsedProject::class)]
final class SourceFileTest extends TestCase
{
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
        $result = (new ProjectSymbolCollector())->collectFile(new SourceFile($root . '/src/App.php', 'src/App.php', 'demo/app', false), 'fingerprint');
        $symbols = $result['symbols'];

        self::assertFalse($result['cached']);
        self::assertInstanceOf(FileSymbols::class, $symbols);
        self::assertCount(1, $symbols->classLikes);
        self::assertSame('Demo\App', $symbols->classLikes[0]->fqcn);
        self::assertCount(1, $result['usages']);
        self::assertSame('Demo\Widget', $result['usages'][0]->targetFqcn);
        self::assertSame('src/App.php', $result['usages'][0]->file);
    }
}

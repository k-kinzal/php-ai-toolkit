<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Parse\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Parse\Internal\AstParser;
use Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder;
use Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader;
use Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Parse\Internal\ExprTextPrinter;
use Toolkit\DocGen\Parse\Internal\FileSymbolCollector;
use Toolkit\DocGen\Parse\Internal\NativeTypePrinter;
use Toolkit\DocGen\Parse\Internal\ParameterModifiers;
use Toolkit\DocGen\Parse\Internal\PhpParserBridge;
use Toolkit\DocGen\Parse\Internal\SymbolContext;
use Toolkit\DocGen\Parse\Internal\UseMapCollector;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Parse\Symbol\ConstantDoc;
use Toolkit\DocGen\Parse\Symbol\FileSymbols;
use Toolkit\DocGen\Parse\Symbol\FunctionDoc;
use Toolkit\DocGen\Parse\Symbol\MethodDoc;
use Toolkit\DocGen\Parse\Symbol\ParameterDoc;
use Toolkit\DocGen\Parse\Symbol\PropertyDoc;
use Toolkit\DocGen\Parse\Symbol\TypeSignature;

/**
 * @covers \Toolkit\DocGen\Parse\Internal\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Internal\AstParser
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ConstantDoc
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\FunctionDoc
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\MethodDoc
 * @uses \Toolkit\DocGen\Parse\Internal\NativeTypePrinter
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ParameterDoc
 * @uses \Toolkit\DocGen\Parse\Internal\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\PhpParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\PropertyDoc
 * @uses \Toolkit\DocGen\Parse\Internal\SymbolContext
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Parse\Internal\UseMapCollector
 */
#[CoversClass(FileSymbolCollector::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(ConstantDoc::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(PropertyDoc::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UseMapCollector::class)]
final class FileSymbolCollectorTest extends TestCase
{
    public function testCollectBuildsNamespacedClassLike(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

use Countable;

final class Sample implements Countable
{
    public const LIMIT = 5;

    private string $name = 'x';

    public function __construct(private int $count)
    {
    }

    public function count(): int
    {
        return $this->count;
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'sample.php');

        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Sample.php', false);

        self::assertCount(1, $symbols->classLikes);
        $classLike = $symbols->classLikes[0];
        self::assertSame('Demo\Sample', $classLike->fqcn);
        self::assertSame('Sample', $classLike->shortName);
        self::assertSame('Demo', $classLike->namespace);
        self::assertSame('class', $classLike->kind);
        self::assertSame('demo/pkg', $classLike->packageName);
        self::assertSame('src/Sample.php', $classLike->file);
        self::assertTrue($classLike->isFinal);
        self::assertSame(['Countable'], $classLike->implements);
        self::assertSame(['countable' => 'Countable'], $classLike->useMap);
        self::assertCount(1, $classLike->constants);
        self::assertSame('LIMIT', $classLike->constants[0]->name);
        self::assertCount(2, $classLike->properties);
        self::assertSame('name', $classLike->properties[0]->name);
        self::assertSame('count', $classLike->properties[1]->name);
        self::assertTrue($classLike->properties[1]->isPromoted);
        self::assertCount(2, $classLike->methods);
        self::assertSame('__construct', $classLike->methods[0]->name);
        self::assertSame('count', $classLike->methods[1]->name);
        self::assertFalse($classLike->isDev);
        self::assertSame([], $symbols->functions);
    }

    public function testCollectCollectsGlobalSymbols(): void
    {
        $code = <<<'PHP'
<?php

class Legacy
{
}

function legacy_helper(): void
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'legacy.php');

        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/legacy.php', true);

        self::assertCount(1, $symbols->classLikes);
        self::assertSame('Legacy', $symbols->classLikes[0]->fqcn);
        self::assertSame('', $symbols->classLikes[0]->namespace);
        self::assertTrue($symbols->classLikes[0]->isDev);
        self::assertCount(1, $symbols->functions);
        self::assertSame('legacy_helper', $symbols->functions[0]->fqn);
        self::assertSame('', $symbols->functions[0]->namespace);
        self::assertTrue($symbols->functions[0]->isDev);
    }

    public function testCollectSeparatesSymbolsByNamespace(): void
    {
        $code = <<<'PHP'
<?php

namespace First;

class A
{
}

namespace Second;

class B
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'multi.php');

        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/multi.php', false);

        self::assertCount(2, $symbols->classLikes);
        self::assertSame('First\A', $symbols->classLikes[0]->fqcn);
        self::assertSame('First', $symbols->classLikes[0]->namespace);
        self::assertSame('Second\B', $symbols->classLikes[1]->fqcn);
        self::assertSame('Second', $symbols->classLikes[1]->namespace);
    }

    public function testNamespaceGroupsGroupsStatementsByNamespace(): void
    {
        $code = <<<'PHP'
<?php

namespace First;

class A
{
}

namespace Second;

class B
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'multi.php');

        $groups = (new FileSymbolCollector())->namespaceGroups($statements);

        self::assertCount(2, $groups);
        self::assertSame('First', $groups[0]['namespace']);
        self::assertCount(1, $groups[0]['statements']);
        self::assertSame('Second', $groups[1]['namespace']);
        self::assertCount(1, $groups[1]['statements']);
    }

    public function testNamespaceGroupsCollectsGlobalStatementsIntoOneGroup(): void
    {
        $statements = (new AstParser())->parse('<?php class A {} function b(): void {}', 'global.php');

        $groups = (new FileSymbolCollector())->namespaceGroups($statements);

        self::assertCount(1, $groups);
        self::assertSame('', $groups[0]['namespace']);
        self::assertCount(2, $groups[0]['statements']);
    }

    public function testNamespaceGroupsReturnsEmptyListForNoStatements(): void
    {
        self::assertSame([], (new FileSymbolCollector())->namespaceGroups([]));
    }
}

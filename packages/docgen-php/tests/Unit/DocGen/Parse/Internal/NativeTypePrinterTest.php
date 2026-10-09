<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Parse\Internal;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
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
use Toolkit\DocGen\Parse\Symbol\FileSymbols;
use Toolkit\DocGen\Parse\Symbol\MethodDoc;
use Toolkit\DocGen\Parse\Symbol\TypeSignature;

/**
 * @covers \Toolkit\DocGen\Parse\Internal\NativeTypePrinter
 * @uses \Toolkit\DocGen\Parse\Internal\AstParser
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\Internal\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\MethodDoc
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\PhpParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\SymbolContext
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Parse\Internal\UseMapCollector
 */
#[CoversClass(NativeTypePrinter::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UseMapCollector::class)]
final class NativeTypePrinterTest extends TestCase
{
    public function testPrintReturnsNullForMissingType(): void
    {
        self::assertNull((new NativeTypePrinter())->print(null));
    }

    public function testPrintPrintsIdentifierType(): void
    {
        $code = <<<'PHP'
<?php

final class Sample
{
    public function count(): int
    {
        return 1;
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'sample.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Sample.php', false);

        self::assertSame('int', $symbols->classLikes[0]->methods[0]->returnType->native);
    }

    public function testPrintPrintsResolvedClassName(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

final class Sample
{
    public function items(): Basket
    {
        return new Basket();
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'sample.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Sample.php', false);

        self::assertSame('Demo\Basket', $symbols->classLikes[0]->methods[0]->returnType->native);
    }

    public function testPrintPrintsNullableType(): void
    {
        $code = <<<'PHP'
<?php

final class Sample
{
    public function label(): ?string
    {
        return null;
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'sample.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Sample.php', false);

        self::assertSame('?string', $symbols->classLikes[0]->methods[0]->returnType->native);
    }

    public function testPrintPrintsUnionType(): void
    {
        $code = <<<'PHP'
<?php

final class Sample
{
    public function value(): int|string
    {
        return 1;
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'sample.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Sample.php', false);

        self::assertSame('int|string', $symbols->classLikes[0]->methods[0]->returnType->native);
    }

    public function testPrintPrintsIntersectionType(): void
    {
        $code = <<<'PHP'
<?php

final class Sample
{
    public function bag(): Countable&Traversable
    {
        return new ArrayIterator([]);
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'sample.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Sample.php', false);

        self::assertSame('Countable&Traversable', $symbols->classLikes[0]->methods[0]->returnType->native);
    }

    public function testPartsPrintsEachCompositeMember(): void
    {
        self::assertSame(['int', 'Demo\Sample'], (new NativeTypePrinter())->parts([new Identifier('int'), new Name('Demo\Sample')]));
    }
}

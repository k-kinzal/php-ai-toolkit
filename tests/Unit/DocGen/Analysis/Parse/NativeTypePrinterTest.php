<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\MethodDoc;
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
use Toolkit\DocGen\Analysis\Parse\SymbolContext;
use Toolkit\DocGen\Analysis\Parse\UseMapCollector;

/**
 * @covers \Toolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbols
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\MethodDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Parse\UseMapCollector
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

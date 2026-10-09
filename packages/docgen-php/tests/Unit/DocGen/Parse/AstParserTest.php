<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Parse;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Parse\AstParser;
use Toolkit\DocGen\Parse\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Parse\Builder\ConstantBuilder;
use Toolkit\DocGen\Parse\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Parse\Builder\FunctionBuilder;
use Toolkit\DocGen\Parse\Builder\MethodBuilder;
use Toolkit\DocGen\Parse\Builder\ParameterBuilder;
use Toolkit\DocGen\Parse\Builder\PropertyBuilder;
use Toolkit\DocGen\Parse\Doc\DocBlockReader;
use Toolkit\DocGen\Parse\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Parse\ExprTextPrinter;
use Toolkit\DocGen\Parse\FileSymbolCollector;
use Toolkit\DocGen\Parse\NativeTypePrinter;
use Toolkit\DocGen\Parse\ParameterModifiers;
use Toolkit\DocGen\Parse\PhpParserBridge;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Parse\Symbol\FileSymbols;
use Toolkit\DocGen\Parse\SymbolContext;
use Toolkit\DocGen\Parse\UseMapCollector;

/**
 * @covers \Toolkit\DocGen\Parse\AstParser
 * @uses \Toolkit\DocGen\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Parse\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Parse\UseMapCollector
 */
#[CoversClass(AstParser::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(UseMapCollector::class)]
final class AstParserTest extends TestCase
{
    public function testParseThrowsOnInvalidSource(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Failed to parse bad.php:');

        (new AstParser())->parse('<?php $x = ;', 'bad.php');
    }

    public function testParseReturnsEmptyListForSourceWithoutStatements(): void
    {
        self::assertSame([], (new AstParser())->parse('<?php ', 'empty.php'));
    }

    public function testParseResolvesImportedNames(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

use Countable;

final class Sample implements Countable
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'sample.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Sample.php', false);

        self::assertCount(1, $symbols->classLikes);
        self::assertSame('Demo\Sample', $symbols->classLikes[0]->fqcn);
        self::assertSame(['Countable'], $symbols->classLikes[0]->implements);
    }

    public function testParseUsesProvidedBridge(): void
    {
        self::assertCount(1, (new AstParser(new PhpParserBridge()))->parse('<?php echo 1;', 'echo.php'));
    }
}

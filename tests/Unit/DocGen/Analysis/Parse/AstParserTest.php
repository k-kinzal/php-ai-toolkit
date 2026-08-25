<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
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
use Toolkit\DocGen\DocGenException;

/**
 * @covers \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbols
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Analysis\Parse\UseMapCollector
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

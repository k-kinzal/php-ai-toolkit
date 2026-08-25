<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;
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
use Toolkit\DocGen\Analysis\Parse\SymbolContext;
use Toolkit\DocGen\Analysis\Parse\UseMapCollector;
use Toolkit\DocGen\Render\Page\Component\SymbolDescription;

/**
 * @covers \Toolkit\DocGen\Render\Page\Component\SymbolDescription
 * @uses \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\DocBlock
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbols
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\FunctionDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ParameterDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Parse\UseMapCollector
 */
#[CoversClass(SymbolDescription::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UseMapCollector::class)]
final class SymbolDescriptionTest extends TestCase
{
    public function testOfClassLikeReadsTheSummaryLineOfTheSymbol(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

/**
 * Widget summary line.
 */
final class Widget
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);

        self::assertSame('Widget summary line.', (new SymbolDescription())->ofClassLike($symbols->classLikes[0]));
    }

    public function testOfClassLikeNamesWhatAnUndocumentedSymbolIs(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

final class Widget
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);

        self::assertSame('The class Demo\Widget of the demo/pkg package.', (new SymbolDescription())->ofClassLike($symbols->classLikes[0]));
    }

    public function testOfFunctionReadsTheSummaryLineOfTheFunction(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

/**
 * Makes a widget count.
 */
function make(int $count): int
{
    return $count;
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/functions.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/functions.php', false);

        self::assertSame('Makes a widget count.', (new SymbolDescription())->ofFunction($symbols->functions[0]));
    }

    public function testOfFunctionNamesAnUndocumentedFunction(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

function make(int $count): int
{
    return $count;
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/functions.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/functions.php', false);

        self::assertSame('The Demo\make() function of the demo/pkg package.', (new SymbolDescription())->ofFunction($symbols->functions[0]));
    }
}

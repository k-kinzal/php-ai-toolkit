<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse\Builder;

use PhpParser\Node\Stmt\Function_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
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

/**
 * @covers \Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbols
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
#[CoversClass(FunctionBuilder::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
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
final class FunctionBuilderTest extends TestCase
{
    public function testBuildBuildsFunctionModel(): void
    {
        $code = <<<'PHP'
<?php

function greet(string $name, int $times = 1): string
{
    return $name;
}
PHP;
        $statement = (new AstParser())->parse($code, 'greet.php')[0];
        self::assertInstanceOf(Function_::class, $statement);
        $context = new SymbolContext('', [], 'demo/pkg', 'src/functions.php', true);

        $doc = (new FunctionBuilder())->build($statement, $context);

        self::assertSame('greet', $doc->fqn);
        self::assertSame('greet', $doc->shortName);
        self::assertSame('', $doc->namespace);
        self::assertSame('demo/pkg', $doc->packageName);
        self::assertSame('src/functions.php', $doc->file);
        self::assertSame(3, $doc->startLine);
        self::assertSame(6, $doc->endLine);
        self::assertCount(2, $doc->parameters);
        self::assertSame('name', $doc->parameters[0]->name);
        self::assertSame('string', $doc->parameters[0]->type->native);
        self::assertNull($doc->parameters[0]->defaultText);
        self::assertSame('times', $doc->parameters[1]->name);
        self::assertSame('int', $doc->parameters[1]->type->native);
        self::assertSame('1', $doc->parameters[1]->defaultText);
        self::assertSame('string', $doc->returnType->native);
        self::assertNull($doc->returnType->annotated);
        self::assertNull($doc->docBlock);
        self::assertSame([], $doc->useMap);
        self::assertTrue($doc->isDev);
    }

    public function testBuildQualifiesNamespacedFunctionName(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

function helper(): void
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'helper.php');

        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/helper.php', false);

        self::assertCount(1, $symbols->functions);
        self::assertSame('Demo\helper', $symbols->functions[0]->fqn);
        self::assertSame('helper', $symbols->functions[0]->shortName);
        self::assertSame('Demo', $symbols->functions[0]->namespace);
        self::assertSame('void', $symbols->functions[0]->returnType->native);
    }
}

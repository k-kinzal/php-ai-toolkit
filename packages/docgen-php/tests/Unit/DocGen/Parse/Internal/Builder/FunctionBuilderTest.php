<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Parse\Internal\Builder;

use PhpParser\Node\Stmt\Function_;
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
use Toolkit\DocGen\Parse\Symbol\FileSymbols;
use Toolkit\DocGen\Parse\Symbol\FunctionDoc;
use Toolkit\DocGen\Parse\Symbol\ParameterDoc;
use Toolkit\DocGen\Parse\Symbol\TypeSignature;

/**
 * @covers \Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\AstParser
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\Internal\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parse\Symbol\FunctionDoc
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\NativeTypePrinter
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ParameterDoc
 * @uses \Toolkit\DocGen\Parse\Internal\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\PhpParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\SymbolContext
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Parse\Internal\UseMapCollector
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

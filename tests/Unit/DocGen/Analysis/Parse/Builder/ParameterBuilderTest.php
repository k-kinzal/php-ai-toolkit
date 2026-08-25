<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse\Builder;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\DocTag;
use Toolkit\DocGen\Analysis\Model\ParameterDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
use Toolkit\DocGen\Analysis\Parse\AstParser;
use Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder;
use Toolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use Toolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use Toolkit\DocGen\Analysis\Parse\ParameterModifiers;
use Toolkit\DocGen\Analysis\Parse\PhpParserBridge;

/**
 * @covers \Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Analysis\Model\DocBlock
 * @uses \Toolkit\DocGen\Analysis\Model\DocTag
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Analysis\Model\ParameterDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 */
#[CoversClass(ParameterBuilder::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DocTag::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(TypeSignature::class)]
final class ParameterBuilderTest extends TestCase
{
    public function testBuildReadsTypedParameterWithDefault(): void
    {
        $statement = (new AstParser())->parse('<?php function fmt(int $width = 8): void {}', 'fmt.php')[0];
        self::assertInstanceOf(Function_::class, $statement);

        $parameter = (new ParameterBuilder())->build($statement->params[0], null);

        self::assertSame('width', $parameter->name);
        self::assertSame('int', $parameter->type->native);
        self::assertNull($parameter->type->annotated);
        self::assertFalse($parameter->byRef);
        self::assertFalse($parameter->variadic);
        self::assertSame('8', $parameter->defaultText);
        self::assertNull($parameter->promotedVisibility);
        self::assertSame('', $parameter->description);
    }

    public function testBuildReadsByRefAndVariadicFlags(): void
    {
        $statement = (new AstParser())->parse('<?php function push(array &$stack, string ...$items): void {}', 'push.php')[0];
        self::assertInstanceOf(Function_::class, $statement);

        $stack = (new ParameterBuilder())->build($statement->params[0], null);
        $items = (new ParameterBuilder())->build($statement->params[1], null);

        self::assertSame('stack', $stack->name);
        self::assertTrue($stack->byRef);
        self::assertFalse($stack->variadic);
        self::assertNull($stack->defaultText);
        self::assertSame('items', $items->name);
        self::assertFalse($items->byRef);
        self::assertTrue($items->variadic);
    }

    public function testBuildReadsPromotedVisibility(): void
    {
        $code = <<<'PHP'
<?php

final class Sample
{
    public function __construct(private int $count)
    {
    }
}
PHP;
        $statement = (new AstParser())->parse($code, 'sample.php')[0];
        self::assertInstanceOf(Class_::class, $statement);
        $constructor = $statement->getMethod('__construct');
        self::assertNotNull($constructor);

        $parameter = (new ParameterBuilder())->build($constructor->params[0], null);

        self::assertSame('count', $parameter->name);
        self::assertSame('private', $parameter->promotedVisibility);
    }

    public function testBuildMergesMatchingParamTag(): void
    {
        $tag = new DocTag(new IdentifierTypeNode('positive-int'), 'column width');
        $docBlock = new DocBlock('', '', ['$width' => $tag], null, null, [], [], [], [], [], [], null, false, '/** @param positive-int $width column width */');
        $statement = (new AstParser())->parse('<?php function fmt(int $width): void {}', 'fmt.php')[0];
        self::assertInstanceOf(Function_::class, $statement);

        $parameter = (new ParameterBuilder())->build($statement->params[0], $docBlock);

        self::assertSame($tag, $parameter->type->annotated);
        self::assertSame('column width', $parameter->description);
    }

    public function testBuildIgnoresUnmatchedParamTag(): void
    {
        $tag = new DocTag(new IdentifierTypeNode('string'), 'unrelated');
        $docBlock = new DocBlock('', '', ['$other' => $tag], null, null, [], [], [], [], [], [], null, false, '/** @param string $other unrelated */');
        $statement = (new AstParser())->parse('<?php function fmt(int $width): void {}', 'fmt.php')[0];
        self::assertInstanceOf(Function_::class, $statement);

        $parameter = (new ParameterBuilder())->build($statement->params[0], $docBlock);

        self::assertNull($parameter->type->annotated);
        self::assertSame('', $parameter->description);
    }
}

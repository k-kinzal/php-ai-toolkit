<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse\Builder;

use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Model\PropertyDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpAiToolkit\DocGen\Analysis\Parse\AstParser;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\AstParser
 * @uses \PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\PropertyDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\TypeSignature
 */
#[CoversClass(PropertyBuilder::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PropertyDoc::class)]
#[UsesClass(TypeSignature::class)]
final class PropertyBuilderTest extends TestCase
{
    public function testBuildBuildsOneModelPerDeclaredProperty(): void
    {
        $code = <<<'PHP'
<?php

final class Sample
{
    protected static ?int $first = 1, $second;
}
PHP;
        $statement = (new AstParser())->parse($code, 'sample.php')[0];
        self::assertInstanceOf(Class_::class, $statement);
        $property = $statement->stmts[0];
        self::assertInstanceOf(Property::class, $property);

        $properties = (new PropertyBuilder())->build($property);

        self::assertCount(2, $properties);
        self::assertSame('first', $properties[0]->name);
        self::assertSame('protected', $properties[0]->visibility);
        self::assertTrue($properties[0]->isStatic);
        self::assertFalse($properties[0]->isPromoted);
        self::assertSame('?int', $properties[0]->type->native);
        self::assertNull($properties[0]->type->annotated);
        self::assertSame('1', $properties[0]->defaultText);
        self::assertNull($properties[0]->docBlock);
        self::assertSame(5, $properties[0]->line);
        self::assertSame('second', $properties[1]->name);
        self::assertSame('protected', $properties[1]->visibility);
        self::assertNull($properties[1]->defaultText);
    }

    public function testBuildReadsPublicInstanceProperty(): void
    {
        $code = <<<'PHP'
<?php

final class Sample
{
    public string $label = 'a';
}
PHP;
        $statement = (new AstParser())->parse($code, 'sample.php')[0];
        self::assertInstanceOf(Class_::class, $statement);
        $property = $statement->stmts[0];
        self::assertInstanceOf(Property::class, $property);

        $properties = (new PropertyBuilder())->build($property);

        self::assertCount(1, $properties);
        self::assertSame('public', $properties[0]->visibility);
        self::assertFalse($properties[0]->isStatic);
        self::assertSame('string', $properties[0]->type->native);
        self::assertSame("'a'", $properties[0]->defaultText);
    }

    public function testBuildReadsPrivateVisibility(): void
    {
        $code = <<<'PHP'
<?php

final class Sample
{
    private bool $done;
}
PHP;
        $statement = (new AstParser())->parse($code, 'sample.php')[0];
        self::assertInstanceOf(Class_::class, $statement);
        $property = $statement->stmts[0];
        self::assertInstanceOf(Property::class, $property);

        $properties = (new PropertyBuilder())->build($property);

        self::assertCount(1, $properties);
        self::assertSame('private', $properties[0]->visibility);
        self::assertSame('bool', $properties[0]->type->native);
        self::assertNull($properties[0]->defaultText);
    }
}

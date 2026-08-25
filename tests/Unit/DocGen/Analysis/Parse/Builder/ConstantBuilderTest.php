<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse\Builder;

use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc;
use PhpAiToolkit\DocGen\Analysis\Parse\AstParser;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\AstParser
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge
 */
#[CoversClass(ConstantBuilder::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(ConstantDoc::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpParserBridge::class)]
final class ConstantBuilderTest extends TestCase
{
    public function testBuildBuildsOneModelPerDeclaredConstant(): void
    {
        $code = <<<'PHP'
<?php

final class Sample
{
    protected const ONE = 1, TWO = 2;
}
PHP;
        $statement = (new AstParser())->parse($code, 'sample.php')[0];
        self::assertInstanceOf(Class_::class, $statement);
        $constant = $statement->stmts[0];
        self::assertInstanceOf(ClassConst::class, $constant);

        $constants = (new ConstantBuilder())->build($constant);

        self::assertCount(2, $constants);
        self::assertSame('ONE', $constants[0]->name);
        self::assertSame('protected', $constants[0]->visibility);
        self::assertSame('1', $constants[0]->valueText);
        self::assertNull($constants[0]->docBlock);
        self::assertSame(5, $constants[0]->line);
        self::assertSame('TWO', $constants[1]->name);
        self::assertSame('protected', $constants[1]->visibility);
        self::assertSame('2', $constants[1]->valueText);
    }

    public function testBuildDefaultsToPublicVisibility(): void
    {
        $code = <<<'PHP'
<?php

final class Sample
{
    const LIMIT = 'x';
}
PHP;
        $statement = (new AstParser())->parse($code, 'sample.php')[0];
        self::assertInstanceOf(Class_::class, $statement);
        $constant = $statement->stmts[0];
        self::assertInstanceOf(ClassConst::class, $constant);

        $constants = (new ConstantBuilder())->build($constant);

        self::assertCount(1, $constants);
        self::assertSame('public', $constants[0]->visibility);
        self::assertSame("'x'", $constants[0]->valueText);
    }

    public function testBuildReadsPrivateVisibility(): void
    {
        $code = <<<'PHP'
<?php

final class Sample
{
    private const SECRET = 7;
}
PHP;
        $statement = (new AstParser())->parse($code, 'sample.php')[0];
        self::assertInstanceOf(Class_::class, $statement);
        $constant = $statement->stmts[0];
        self::assertInstanceOf(ClassConst::class, $constant);

        $constants = (new ConstantBuilder())->build($constant);

        self::assertCount(1, $constants);
        self::assertSame('private', $constants[0]->visibility);
        self::assertSame('7', $constants[0]->valueText);
    }
}

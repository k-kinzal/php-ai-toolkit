<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse\Builder;

use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Model\EnumCaseDoc;
use PhpAiToolkit\DocGen\Analysis\Parse\AstParser;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\EnumCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\AstParser
 * @uses \PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\EnumCaseDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge
 */
#[CoversClass(EnumCaseBuilder::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(EnumCaseDoc::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpParserBridge::class)]
final class EnumCaseBuilderTest extends TestCase
{
    public function testBuildReadsBackedCase(): void
    {
        $code = <<<'PHP'
<?php

enum Suit: string
{
    case Hearts = 'h';
}
PHP;
        $statement = (new AstParser())->parse($code, 'suit.php')[0];
        self::assertInstanceOf(Enum_::class, $statement);
        $case = $statement->stmts[0];
        self::assertInstanceOf(EnumCase::class, $case);

        $doc = (new EnumCaseBuilder())->build($case);

        self::assertSame('Hearts', $doc->name);
        self::assertSame("'h'", $doc->valueText);
        self::assertNull($doc->docBlock);
        self::assertSame(5, $doc->line);
    }

    public function testBuildReadsPureCaseWithoutValue(): void
    {
        $code = <<<'PHP'
<?php

enum Direction
{
    case North;
}
PHP;
        $statement = (new AstParser())->parse($code, 'direction.php')[0];
        self::assertInstanceOf(Enum_::class, $statement);
        $case = $statement->stmts[0];
        self::assertInstanceOf(EnumCase::class, $case);

        $doc = (new EnumCaseBuilder())->build($case);

        self::assertSame('North', $doc->name);
        self::assertNull($doc->valueText);
        self::assertNull($doc->docBlock);
        self::assertSame(5, $doc->line);
    }
}

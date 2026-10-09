<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Parse\Builder;

use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\EnumCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Parse\AstParser;
use Toolkit\DocGen\Parse\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Parse\Doc\DocBlockReader;
use Toolkit\DocGen\Parse\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Parse\ExprTextPrinter;
use Toolkit\DocGen\Parse\PhpParserBridge;
use Toolkit\DocGen\Parse\Symbol\EnumCaseDoc;

/**
 * @covers \Toolkit\DocGen\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Parse\AstParser
 * @uses \Toolkit\DocGen\Parse\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Parse\Symbol\EnumCaseDoc
 * @uses \Toolkit\DocGen\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Parse\PhpParserBridge
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

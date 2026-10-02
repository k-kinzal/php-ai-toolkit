<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Toolkit\DocGen\Analysis\Parse\PhpParserBridge;
use Toolkit\PhpStan\Rule\Shared\ThrownExpression;

/**
 * @covers \Toolkit\PhpStan\Rule\Shared\ThrownExpression
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 */
#[CoversClass(ThrownExpression::class)]
#[UsesClass(PhpParserBridge::class)]
final class ThrownExpressionTest extends TestCase
{
    public function testOfReadsTheExpressionOfAThrowExpression(): void
    {
        $throw = new Throw_(new New_(new Name('RuntimeException')));

        self::assertSame($throw->expr, (new ThrownExpression())->of($throw));
    }

    /**
     * @dataProvider providerParsedThrow
     */
    #[DataProvider('providerParsedThrow')]
    public function testOfReadsTheExpressionOfTheThrowTheParserProduces(Node $throw): void
    {
        self::assertInstanceOf(New_::class, (new ThrownExpression())->of($throw));
    }

    public function testOfReadsNothingFromANodeThatThrowsNothing(): void
    {
        self::assertNull((new ThrownExpression())->of(new Expression(new Variable('value'))));
    }

    /**
     * @dataProvider providerParsedThrow
     */
    #[DataProvider('providerParsedThrow')]
    public function testIsThrowRecognizesTheThrowTheParserProduces(Node $throw): void
    {
        self::assertTrue((new ThrownExpression())->isThrow($throw));
    }

    public function testIsThrowRejectsANodeThatThrowsNothing(): void
    {
        self::assertFalse((new ThrownExpression())->isThrow(new Expression(new Variable('value'))));
    }

    /**
     * @return array<string, array{Node}>
     *
     * @throws RuntimeException when the installed parser produces no statement
     */
    public static function providerParsedThrow(): array
    {
        $statements = (new PhpParserBridge())->parser()->parse('<?php throw new RuntimeException("boom");');
        $statement = $statements[0] ?? null;
        if ($statement === null) {
            throw new RuntimeException('The installed parser produced no statement from the snippet.');
        }

        return ['the throw of the installed parser' => [$statement instanceof Expression ? $statement->expr : $statement]];
    }
}

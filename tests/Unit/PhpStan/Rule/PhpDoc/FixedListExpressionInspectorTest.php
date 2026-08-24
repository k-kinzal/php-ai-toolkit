<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PhpAiToolkit\PhpStan\Rule\PhpDoc\FixedListExpressionInspector;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FixedListExpressionInspector::class)]
final class FixedListExpressionInspectorTest extends TestCase
{
    public function testIsNonEmptyListRequiresImplicitKeysAndAtLeastOneItem(): void
    {
        $statements = (new ParserFactory())->createForHostVersion()->parse(<<<'PHP'
            <?php
            return ['foo', 'bar'];
            return [];
            return [1 => 'foo'];
            PHP);
        self::assertNotNull($statements);
        self::assertInstanceOf(Return_::class, $statements[0]);
        self::assertInstanceOf(Array_::class, $statements[0]->expr);
        self::assertInstanceOf(Return_::class, $statements[1]);
        self::assertInstanceOf(Array_::class, $statements[1]->expr);
        self::assertInstanceOf(Return_::class, $statements[2]);
        self::assertInstanceOf(Array_::class, $statements[2]->expr);

        self::assertTrue((new FixedListExpressionInspector())->isNonEmptyList($statements[0]->expr));
        self::assertFalse((new FixedListExpressionInspector())->isNonEmptyList($statements[1]->expr));
        self::assertFalse((new FixedListExpressionInspector())->isNonEmptyList($statements[2]->expr));
    }

    public function testIsImplicitArrayItemDistinguishesImplicitKeysAndMissingItems(): void
    {
        $statements = (new ParserFactory())->createForHostVersion()->parse(<<<'PHP'
            <?php
            return ['foo'];
            return [1 => 'bar'];
            PHP);
        self::assertNotNull($statements);
        self::assertInstanceOf(Return_::class, $statements[0]);
        self::assertInstanceOf(Array_::class, $statements[0]->expr);
        self::assertInstanceOf(Return_::class, $statements[1]);
        self::assertInstanceOf(Array_::class, $statements[1]->expr);
        $inspector = new FixedListExpressionInspector();

        self::assertTrue($inspector->isImplicitArrayItem($statements[0]->expr->items[0]));
        self::assertFalse($inspector->isImplicitArrayItem($statements[1]->expr->items[0]));
        self::assertFalse($inspector->isImplicitArrayItem(null));
    }

    public function testCallableReturnsFixedListsIgnoresNestedScopesAndNonArrayBranches(): void
    {
        $statements = (new ParserFactory())->createForHostVersion()->parse(<<<'PHP'
            <?php
            function names(bool $found): ?array
            {
                $nested = function (): array {
                    return [1 => 'not owned'];
                };
                if ($found) {
                    return ['foo', 'bar'];
                }
                return null;
            }
            PHP);
        self::assertNotNull($statements);
        self::assertInstanceOf(Function_::class, $statements[0]);

        self::assertTrue((new FixedListExpressionInspector())->callableReturnsFixedLists($statements[0]));
    }

    public function testCallableReturnsFixedListsRejectsSparseAndUncertainReturns(): void
    {
        $statements = (new ParserFactory())->createForHostVersion()->parse(<<<'PHP'
            <?php
            function sparse(bool $found): array
            {
                return $found ? ['foo'] : [2 => 'bar'];
            }

            function uncertain(array $values): array
            {
                if ($values === []) {
                    return ['foo'];
                }
                return $values;
            }
            PHP);
        self::assertNotNull($statements);
        self::assertInstanceOf(Function_::class, $statements[0]);
        self::assertInstanceOf(Function_::class, $statements[1]);

        self::assertFalse((new FixedListExpressionInspector())->callableReturnsFixedLists($statements[0]));
        self::assertFalse((new FixedListExpressionInspector())->callableReturnsFixedLists($statements[1]));
    }

    public function testOwnedReturnsExcludesReturnsFromNestedFunctions(): void
    {
        $statements = (new ParserFactory())->createForHostVersion()->parse(<<<'PHP'
            <?php
            return ['owned'];
            function nested(): array
            {
                return ['nested'];
            }
            PHP);
        self::assertNotNull($statements);

        $returns = (new FixedListExpressionInspector())->ownedReturns($statements);

        self::assertCount(1, $returns);
        self::assertSame(2, $returns[0]->getStartLine());
    }

    public function testCollectReturnsAddsReturnsNestedInControlFlow(): void
    {
        $statements = (new ParserFactory())->createForHostVersion()->parse(<<<'PHP'
            <?php
            if ($found) {
                return ['foo'];
            }
            PHP);
        self::assertNotNull($statements);
        $returns = [];

        (new FixedListExpressionInspector())->collectReturns($statements[0], $returns);

        self::assertCount(1, $returns);
    }

    public function testIsDefinitelyNonArrayDistinguishesLiteralsFromVariables(): void
    {
        $statements = (new ParserFactory())->createForHostVersion()->parse(<<<'PHP'
            <?php
            return 'foo';
            return null;
            return $value;
            PHP);
        self::assertNotNull($statements);
        self::assertInstanceOf(Return_::class, $statements[0]);
        self::assertInstanceOf(Return_::class, $statements[1]);
        self::assertInstanceOf(Return_::class, $statements[2]);
        $inspector = new FixedListExpressionInspector();
        self::assertNotNull($statements[0]->expr);
        self::assertNotNull($statements[1]->expr);
        self::assertNotNull($statements[2]->expr);

        self::assertTrue($inspector->isDefinitelyNonArray($statements[0]->expr));
        self::assertTrue($inspector->isDefinitelyNonArray($statements[1]->expr));
        self::assertFalse($inspector->isDefinitelyNonArray($statements[2]->expr));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use Override;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\ParserFactory;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\PhpDoc\FixedListExpressionInspector;
use Toolkit\PhpStan\Rule\PhpDoc\ListTypeDeclarationInspector;
use Toolkit\PhpStan\Rule\PhpDoc\RequireListForArrayLiteralRule;
use Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;

/**
 * @extends RuleTestCase<RequireListForArrayLiteralRule>
 * @covers \Toolkit\PhpStan\Rule\PhpDoc\RequireListForArrayLiteralRule
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\FixedListExpressionInspector
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\ListTypeDeclarationInspector
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser
 */
#[CoversClass(RequireListForArrayLiteralRule::class)]
#[UsesClass(FixedListExpressionInspector::class)]
#[UsesClass(ListTypeDeclarationInspector::class)]
#[UsesClass(RulePhpDocParser::class)]
#[Medium]
final class RequireListForArrayLiteralRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new RequireListForArrayLiteralRule();
    }

    public function testGetNodeTypeReturnsStatementClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeReportsPropertyAndReturnListDeclarations(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/RequireListForArrayLiteral/WithArrayIntListType.php'], [
            [
                'Replace "array<int, string>" with "list<string>" in @var on $names; $names is initialized with a non-empty list literal, so declare its zero-based contiguous keys as part of the property type.',
                10,
            ],
            [
                'Replace "array<int, non-empty-string>" with "list<non-empty-string>" in @phpstan-var on $phpstanNames; $phpstanNames is initialized with a non-empty list literal, so declare its zero-based contiguous keys as part of the property type.',
                13,
            ],
            [
                'Replace "array<int, string>" with "list<string>" in @var on $first; $first is initialized with a non-empty list literal, so declare its zero-based contiguous keys as part of the property type.',
                16,
            ],
            [
                'Replace "array<int, string>" with "list<string>" in @return on names(); names() returns a non-empty list literal, so declare its zero-based contiguous keys as part of the return type.',
                21,
            ],
            [
                'Replace "array<int, non-empty-string>" with "list<non-empty-string>" in @return on nullableNames(); nullableNames() returns a non-empty list literal, so declare its zero-based contiguous keys as part of the return type.',
                29,
            ],
            [
                'Replace "array<int, string>" with "list<string>" in @psalm-return on psalmNames(); psalmNames() returns a non-empty list literal, so declare its zero-based contiguous keys as part of the return type.',
                41,
            ],
            [
                'Replace "array<int, string>" with "list<string>" in @return on arrayIntNames(); arrayIntNames() returns a non-empty list literal, so declare its zero-based contiguous keys as part of the return type.',
                50,
            ],
        ]);
    }

    public function testProcessNodeIgnoresParametersAndNonListOrUncertainValues(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/RequireListForArrayLiteral/WithoutArrayIntListType.php'], []);
    }

    public function testPropertyErrorsNamesTheInitializedProperty(): void
    {
        $statements = (new ParserFactory())->createForHostVersion()->parse(<<<'PHP'
            <?php
            final class Example
            {
                /** @var array<int, string> */
                public array $names = ['foo'];
            }
            PHP);
        self::assertNotNull($statements);
        self::assertInstanceOf(Class_::class, $statements[0]);
        self::assertInstanceOf(Property::class, $statements[0]->stmts[0]);

        $errors = (new RequireListForArrayLiteralRule())->propertyErrors($statements[0]->stmts[0]);

        self::assertCount(1, $errors);
        self::assertSame('customRules.arrayLiteralListType', $errors[0]->getIdentifier());
    }

    public function testReturnErrorsNamesTheCallable(): void
    {
        $statements = (new ParserFactory())->createForHostVersion()->parse(<<<'PHP'
            <?php
            final class Example
            {
                /** @return array<int, string> */
                public function names(): array
                {
                    return ['foo'];
                }
            }
            PHP);
        self::assertNotNull($statements);
        self::assertInstanceOf(Class_::class, $statements[0]);
        self::assertInstanceOf(ClassMethod::class, $statements[0]->stmts[0]);

        $errors = (new RequireListForArrayLiteralRule())->returnErrors($statements[0]->stmts[0]);

        self::assertCount(1, $errors);
        self::assertSame('customRules.arrayLiteralListType', $errors[0]->getIdentifier());
    }
}

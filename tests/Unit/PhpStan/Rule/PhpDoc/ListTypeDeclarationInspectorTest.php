<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\PhpDoc\ListTypeDeclarationInspector;
use Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;

/**
 * @covers \Toolkit\PhpStan\Rule\PhpDoc\ListTypeDeclarationInspector
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser
 */
#[CoversClass(ListTypeDeclarationInspector::class)]
#[UsesClass(RulePhpDocParser::class)]
final class ListTypeDeclarationInspectorTest extends TestCase
{
    public function testReturnDeclarationsFindsOrdinaryAndToolSpecificTags(): void
    {
        $declarations = (new ListTypeDeclarationInspector())->returnDeclarations(<<<'PHPDOC'
            /**
             * @return array<int, string>|null
             * @phpstan-return array<int, array<string, bool>>
             * @psalm-return array<int, covariant Animal>
             */
            PHPDOC);

        self::assertSame([
            ['tag' => '@return', 'type' => 'array<int, string>', 'replacement' => 'list<string>'],
            ['tag' => '@phpstan-return', 'type' => 'array<int, array<string, bool>>', 'replacement' => 'list<array<string, bool>>'],
            ['tag' => '@psalm-return', 'type' => 'array<int, covariant Animal>', 'replacement' => 'list<covariant Animal>'],
        ], $declarations);
    }

    public function testPropertyDeclarationsPreservesOptionalVariableNames(): void
    {
        $declarations = (new ListTypeDeclarationInspector())->propertyDeclarations(<<<'PHPDOC'
            /**
             * @var array<int, string> $names
             * @phpstan-var array<int, int>
             * @psalm-var array<int, bool> $flags
             */
            PHPDOC);

        self::assertSame([
            ['tag' => '@var', 'type' => 'array<int, string>', 'replacement' => 'list<string>', 'variable' => '$names'],
            ['tag' => '@phpstan-var', 'type' => 'array<int, int>', 'replacement' => 'list<int>', 'variable' => ''],
            ['tag' => '@psalm-var', 'type' => 'array<int, bool>', 'replacement' => 'list<bool>', 'variable' => '$flags'],
        ], $declarations);
    }

    public function testDeclarationsIgnoreOtherKeyTypesAndNestedArrayIntTypes(): void
    {
        $declarations = (new ListTypeDeclarationInspector())->returnDeclarations(<<<'PHPDOC'
            /**
             * @return list<string>|array<string, string>|array<string, array<int, string>>
             */
            PHPDOC);

        self::assertSame([], $declarations);
    }

    public function testIsArrayIntTypeRequiresExactlyTwoGenericArguments(): void
    {
        $parser = new RulePhpDocParser();
        $arrayInt = $parser->parse('/** @return array<int, string> */')->getReturnTagValues('@return')[0]->type;
        $implicitKey = $parser->parse('/** @return array<string> */')->getReturnTagValues('@return')[0]->type;

        self::assertTrue((new ListTypeDeclarationInspector())->isArrayIntType($arrayInt));
        self::assertFalse((new ListTypeDeclarationInspector())->isArrayIntType($implicitKey));
    }

    public function testReplacementsInspectsNullableAndUnionBranchesOnly(): void
    {
        $type = (new RulePhpDocParser())
            ->parse('/** @return array<int, string>|null|list<array<int, bool>> */')
            ->getReturnTagValues('@return')[0]
            ->type;

        self::assertSame([
            ['type' => 'array<int, string>', 'replacement' => 'list<string>'],
        ], (new ListTypeDeclarationInspector())->replacements($type));
    }

    public function testValueTypePreservesGenericVariance(): void
    {
        $type = (new RulePhpDocParser())
            ->parse('/** @return array<int, covariant Animal> */')
            ->getReturnTagValues('@return')[0]
            ->type;
        self::assertInstanceOf(GenericTypeNode::class, $type);

        self::assertSame('covariant Animal', (new ListTypeDeclarationInspector())->valueType($type));
    }
}

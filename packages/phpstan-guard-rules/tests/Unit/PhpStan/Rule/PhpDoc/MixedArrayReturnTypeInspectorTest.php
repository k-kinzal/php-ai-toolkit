<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\PhpDoc\MixedArrayReturnTypeInspector;
use Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;

/**
 * @covers \Toolkit\PhpStan\Rule\PhpDoc\MixedArrayReturnTypeInspector
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser
 */
#[CoversClass(MixedArrayReturnTypeInspector::class)]
#[UsesClass(RulePhpDocParser::class)]
final class MixedArrayReturnTypeInspectorTest extends TestCase
{
    public function testDeclarationsFindsOrdinaryAndToolSpecificReturnTags(): void
    {
        $declarations = (new MixedArrayReturnTypeInspector())->declarations(<<<'PHPDOC'
            /**
             * @return array<string, mixed>
             * @phpstan-return non-empty-array<int, mixed>
             * @psalm-return array<mixed>
             */
            PHPDOC);

        self::assertSame([
            ['tag' => '@return', 'type' => 'array<string, mixed>'],
            ['tag' => '@phpstan-return', 'type' => 'non-empty-array<int, mixed>'],
            ['tag' => '@psalm-return', 'type' => 'array<mixed>'],
        ], $declarations);
    }

    public function testDeclarationsFindsAnArrayNestedInAReturnType(): void
    {
        $declarations = (new MixedArrayReturnTypeInspector())->declarations('/** @return list<array<string, mixed>> */');

        self::assertSame([
            ['tag' => '@return', 'type' => 'list<array<string, mixed>>'],
        ], $declarations);
    }

    public function testContainsMixedArrayFindsANestedGenericArray(): void
    {
        $type = (new RulePhpDocParser())
            ->parse('/** @return list<array<string, mixed>> */')
            ->getReturnTagValues('@return')[0]
            ->type;

        self::assertTrue((new MixedArrayReturnTypeInspector())->containsMixedArray($type));
    }

    public function testIsMixedArrayDistinguishesArrayValuesFromOtherGenerics(): void
    {
        $parser = new RulePhpDocParser();
        $mixedArray = $parser->parse('/** @return array<string, mixed> */')->getReturnTagValues('@return')[0]->type;
        $mixedList = $parser->parse('/** @return list<mixed> */')->getReturnTagValues('@return')[0]->type;

        self::assertTrue((new MixedArrayReturnTypeInspector())->isMixedArray($mixedArray));
        self::assertFalse((new MixedArrayReturnTypeInspector())->isMixedArray($mixedList));
    }

    public function testDeclarationsIgnoresMixedOutsideAGenericArrayReturn(): void
    {
        $declarations = (new MixedArrayReturnTypeInspector())->declarations(<<<'PHPDOC'
            /**
             * @param array<string, mixed> $input
             * @var array<int, mixed>
             * @return list<mixed>
             */
            PHPDOC);

        self::assertSame([], $declarations);
    }

    public function testDeclarationsIgnoresSpecificArraysAndMixedShapeFields(): void
    {
        $declarations = (new MixedArrayReturnTypeInspector())->declarations(<<<'PHPDOC'
            /**
             * @return array<string, bool|int|string>|array{payload: mixed}
             */
            PHPDOC);

        self::assertSame([], $declarations);
    }
}

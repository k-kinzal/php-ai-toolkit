<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\PhpDoc\ForbidMixedArrayReturnTypeRule;
use Toolkit\PhpStan\Rule\PhpDoc\MixedArrayReturnTypeInspector;
use Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;
use Toolkit\PhpStan\Rule\Shared\Path\RulePathMatcher;
use Toolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer;

/**
 * @extends RuleTestCase<ForbidMixedArrayReturnTypeRule>
 * @covers \Toolkit\PhpStan\Rule\PhpDoc\ForbidMixedArrayReturnTypeRule
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\MixedArrayReturnTypeInspector
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser
 * @uses \Toolkit\PhpStan\Rule\Shared\Path\RulePathMatcher
 * @uses \Toolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer
 */
#[CoversClass(ForbidMixedArrayReturnTypeRule::class)]
#[UsesClass(MixedArrayReturnTypeInspector::class)]
#[UsesClass(RulePhpDocParser::class)]
#[UsesClass(RulePathMatcher::class)]
#[UsesClass(RulePathNormalizer::class)]
#[Medium]
final class ForbidMixedArrayReturnTypeRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidMixedArrayReturnTypeRule([
            'tests/Fixture/ForbidMixedArrayReturnType/AllowedMixedArrayReturnType.php',
        ]);
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\FunctionLike::class, $this->getRule()->getNodeType());
    }

    public function testCallableNameNamesMethodsAndFunctions(): void
    {
        $rule = new ForbidMixedArrayReturnTypeRule();

        self::assertSame('load()', $rule->callableName(new \PhpParser\Node\Stmt\ClassMethod('load')));
        self::assertSame('decode()', $rule->callableName(new \PhpParser\Node\Stmt\Function_('decode')));
    }

    public function testProcessNodeMixedArrayReturnsAreReported(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/ForbidMixedArrayReturnType/WithMixedArrayReturnType.php'], [
            [
                'Replace "@return array<string, mixed>" on withStringKeys() with an array value type that describes every returned value. Use a union, array shape, DTO, or domain object instead of mixed.',
                12,
            ],
            [
                'Replace "@return array<mixed>" on withImplicitKeys() with an array value type that describes every returned value. Use a union, array shape, DTO, or domain object instead of mixed.',
                20,
            ],
            [
                'Replace "@return (array<int, mixed> | null)" on withNullableArray() with an array value type that describes every returned value. Use a union, array shape, DTO, or domain object instead of mixed.',
                28,
            ],
            [
                'Replace "@return list<array<string, mixed>>" on withNestedArray() with an array value type that describes every returned value. Use a union, array shape, DTO, or domain object instead of mixed.',
                36,
            ],
            [
                'Replace "@phpstan-return non-empty-array<string, mixed>" on withPhpStanReturn() with an array value type that describes every returned value. Use a union, array shape, DTO, or domain object instead of mixed.',
                45,
            ],
            [
                'Replace "@psalm-return array<array-key, mixed>" on withPsalmReturn() with an array value type that describes every returned value. Use a union, array shape, DTO, or domain object instead of mixed.',
                53,
            ],
            [
                'Replace "@return array<string, mixed>" on mixedArrayFunction() with an array value type that describes every returned value. Use a union, array shape, DTO, or domain object instead of mixed.',
                62,
            ],
        ]);
    }

    public function testProcessNodeOtherMixedDeclarationsAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/ForbidMixedArrayReturnType/WithoutMixedArrayReturnType.php'], []);
    }

    public function testProcessNodeAllowedBoundaryPathIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/ForbidMixedArrayReturnType/AllowedMixedArrayReturnType.php'], []);
    }
}

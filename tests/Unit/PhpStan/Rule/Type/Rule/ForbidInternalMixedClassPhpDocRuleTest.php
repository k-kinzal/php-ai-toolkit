<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Type\Rule;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;
use Toolkit\PhpStan\Rule\Type\ConcreteMixedTypeInspector;
use Toolkit\PhpStan\Rule\Type\MixedClassPhpDocErrorCollector;
use Toolkit\PhpStan\Rule\Type\Rule\ForbidInternalMixedClassPhpDocRule;

/**
 * @extends RuleTestCase<ForbidInternalMixedClassPhpDocRule>
 * @covers \Toolkit\PhpStan\Rule\Type\Rule\ForbidInternalMixedClassPhpDocRule
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser
 * @uses \Toolkit\PhpStan\Rule\Type\ConcreteMixedTypeInspector
 * @uses \Toolkit\PhpStan\Rule\Type\MixedClassPhpDocErrorCollector
 */
#[CoversClass(ForbidInternalMixedClassPhpDocRule::class)]
#[UsesClass(ConcreteMixedTypeInspector::class)]
#[UsesClass(MixedClassPhpDocErrorCollector::class)]
#[UsesClass(RulePhpDocParser::class)]
#[Medium]
final class ForbidInternalMixedClassPhpDocRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidInternalMixedClassPhpDocRule();
    }

    public function testGetNodeTypeReturnsClassNode(): void
    {
        self::assertSame(\PHPStan\Node\InClassNode::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeReportsRestrictedVirtualContracts(): void
    {
        $guidance = ': this declaration is internal or scope-restricted, so it must state a deterministic PHPStan type. Validate arbitrary input at an unrestricted public boundary, then pass the narrowed type inward.';
        $prefix = 'Tests\Fixture\ForbidInternalMixedType\RestrictedTypes';
        $arrayType = (new ConcreteMixedTypeInspector())->describe(new ArrayType(new MixedType(false), new MixedType(true)));
        $this->analyse([__DIR__ . '/../../../../../Fixture/ForbidInternalMixedType/ForbiddenMixedTypes.php'], [
            ['Replace concrete mixed type "' . $arrayType . '" in parameter $input of ' . $prefix . '::virtualCall()' . $guidance, 13],
            ['Replace concrete mixed type "array<string, mixed>" in virtual property type of ' . $prefix . '::$virtualPayload' . $guidance, 13],
            ['Replace concrete mixed type "array{payload: mixed}" in type alias Payload of ' . $prefix . $guidance, 13],
            ['Replace concrete mixed type "mixed" in return type of ' . $prefix . '::virtualCall()' . $guidance, 13],
        ]);
        $this->analyse([__DIR__ . '/../../../../../Fixture/ForbidInternalMixedType/AllowedMixedTypes.php'], []);
    }
}

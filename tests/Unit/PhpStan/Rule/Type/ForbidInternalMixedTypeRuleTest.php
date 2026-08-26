<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Type;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\ClassDesign\MagicMethodRegistry;
use Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;
use Toolkit\PhpStan\Rule\Type\ConcreteMixedTypeInspector;
use Toolkit\PhpStan\Rule\Type\ForbidInternalMixedTypeRule;
use Toolkit\PhpStan\Rule\Type\InheritedMixedContractInspector;
use Toolkit\PhpStan\Rule\Type\MixedCallableErrorCollector;
use Toolkit\PhpStan\Rule\Type\MixedTypeErrorBuilder;
use Toolkit\PhpStan\Rule\Type\MixedVisibilityDetector;

/**
 * @extends RuleTestCase<ForbidInternalMixedTypeRule>
 * @covers \Toolkit\PhpStan\Rule\Type\ForbidInternalMixedTypeRule
 * @uses \Toolkit\PhpStan\Rule\ClassDesign\MagicMethodRegistry
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser
 * @uses \Toolkit\PhpStan\Rule\Type\ConcreteMixedTypeInspector
 * @uses \Toolkit\PhpStan\Rule\Type\InheritedMixedContractInspector
 * @uses \Toolkit\PhpStan\Rule\Type\MixedCallableErrorCollector
 * @uses \Toolkit\PhpStan\Rule\Type\MixedTypeErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\Type\MixedVisibilityDetector
 */
#[CoversClass(ForbidInternalMixedTypeRule::class)]
#[UsesClass(ConcreteMixedTypeInspector::class)]
#[UsesClass(InheritedMixedContractInspector::class)]
#[UsesClass(MagicMethodRegistry::class)]
#[UsesClass(MixedCallableErrorCollector::class)]
#[UsesClass(MixedTypeErrorBuilder::class)]
#[UsesClass(MixedVisibilityDetector::class)]
#[UsesClass(RulePhpDocParser::class)]
#[Medium]
final class ForbidInternalMixedTypeRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidInternalMixedTypeRule();
    }

    public function testGetNodeTypeCoversResolvedVirtualNodes(): void
    {
        self::assertSame(\PHPStan\Node\InClassMethodNode::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeAllowsPublicMagicAndTemplateTypes(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/ForbidInternalMixedType/AllowedMixedTypes.php'], []);
    }

    public function testProcessNodeAllowsInheritedMixedOnlyAtMatchingContractPositions(): void
    {
        $guidance = ': this declaration is internal or scope-restricted, so it must state a deterministic PHPStan type. Validate arbitrary input at an unrestricted public boundary, then pass the narrowed type inward.';
        $this->analyse([__DIR__ . '/../../../../Fixture/ForbidInternalMixedType/InheritedMixedTypes.php'], [
            [
                'Replace concrete mixed type "mixed" in parameter $value of Tests\Fixture\ForbidInternalMixedType\MixedImplementation::widenedParameter()' . $guidance,
                64,
            ],
            [
                'Replace concrete mixed type "mixed" in parameter $value of Tests\Fixture\ForbidInternalMixedType\MixedImplementation::ownMethod()' . $guidance,
                69,
            ],
            [
                'Replace concrete mixed type "mixed" in return type of Tests\Fixture\ForbidInternalMixedType\MixedImplementation::ownMethod()' . $guidance,
                69,
            ],
        ]);
    }

    public function testProcessNodeReportsInternalConcreteMixed(): void
    {
        $guidance = ': this declaration is internal or scope-restricted, so it must state a deterministic PHPStan type. Validate arbitrary input at an unrestricted public boundary, then pass the narrowed type inward.';
        $arrayOfMixed = (new ConcreteMixedTypeInspector())->describe(new ArrayType(new MixedType(false), new MixedType(true)));
        $listType = $arrayOfMixed === 'array' ? 'array<int, mixed>' : 'list<mixed>';
        $this->analyse([__DIR__ . '/../../../../Fixture/ForbidInternalMixedType/ForbiddenMixedTypes.php'], [
            ['Replace concrete mixed type "array<string, mixed>" in parameter $payload of Tests\Fixture\ForbidInternalMixedType\RestrictedTypes::transform()' . $guidance, 22],
            ['Replace concrete mixed type "' . $listType . '" in return type of Tests\Fixture\ForbidInternalMixedType\RestrictedTypes::transform()' . $guidance, 22],
            ['Replace concrete mixed type "mixed" in parameter $value of Tests\Fixture\ForbidInternalMixedType\RestrictedLifecycle::__construct()' . $guidance, 68],
            ['Replace concrete mixed type "mixed" in parameter $value of Tests\Fixture\ForbidInternalMixedType\MemberRestrictedTypes::restrictedMember()' . $guidance, 84],
            ['Replace concrete mixed type "mixed" in return type of Tests\Fixture\ForbidInternalMixedType\MemberRestrictedTypes::restrictedMember()' . $guidance, 84],
        ]);
    }
}

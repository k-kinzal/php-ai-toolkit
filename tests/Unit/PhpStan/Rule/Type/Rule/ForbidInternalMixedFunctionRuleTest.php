<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Type\Rule;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\Type\MixedCallableErrorCollector;
use Toolkit\PhpStan\Rule\Type\Rule\ForbidInternalMixedFunctionRule;

/**
 * @extends RuleTestCase<ForbidInternalMixedFunctionRule>
 */
#[CoversClass(ForbidInternalMixedFunctionRule::class)]
#[UsesClass(MixedCallableErrorCollector::class)]
#[Medium]
final class ForbidInternalMixedFunctionRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidInternalMixedFunctionRule();
    }

    public function testGetNodeTypeReturnsFunctionNode(): void
    {
        self::assertSame(\PHPStan\Node\InFunctionNode::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeHonorsFunctionVisibility(): void
    {
        $guidance = ': this declaration is internal or scope-restricted, so it must state a deterministic PHPStan type. Validate arbitrary input at an unrestricted public boundary, then pass the narrowed type inward.';
        $this->analyse([__DIR__ . '/../../../../../Fixture/ForbidInternalMixedType/ForbiddenMixedTypes.php'], [
            ['Replace concrete mixed type "mixed" in parameter $input of Tests\Fixture\ForbidInternalMixedType\restrictedFunction()' . $guidance, 48],
            ['Replace concrete mixed type "mixed" in return type of Tests\Fixture\ForbidInternalMixedType\restrictedFunction()' . $guidance, 48],
        ]);
        $this->analyse([__DIR__ . '/../../../../../Fixture/ForbidInternalMixedType/AllowedMixedTypes.php'], []);
    }
}

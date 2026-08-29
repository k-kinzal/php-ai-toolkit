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
use Toolkit\PhpStan\Rule\Type\Rule\ForbidInternalMixedArrowFunctionRule;

/**
 * @extends RuleTestCase<ForbidInternalMixedArrowFunctionRule>
 * @covers \Toolkit\PhpStan\Rule\Type\Rule\ForbidInternalMixedArrowFunctionRule
 * @uses \Toolkit\PhpStan\Rule\Type\MixedCallableErrorCollector
 * @medium
 */
#[CoversClass(ForbidInternalMixedArrowFunctionRule::class)]
#[UsesClass(MixedCallableErrorCollector::class)]
#[Medium]
final class ForbidInternalMixedArrowFunctionRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidInternalMixedArrowFunctionRule();
    }

    public function testGetNodeTypeReturnsArrowFunctionNode(): void
    {
        self::assertSame(\PHPStan\Node\InArrowFunctionNode::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeReportsArrowFunctionSignature(): void
    {
        $guidance = ': this declaration is internal or scope-restricted, so it must state a deterministic PHPStan type. Validate arbitrary input at an unrestricted public boundary, then pass the narrowed type inward.';
        $this->analyse([__DIR__ . '/../../../../../Fixture/ForbidInternalMixedType/ForbiddenMixedTypes.php'], [
            ['Replace concrete mixed type "mixed" in parameter $input of anonymous function' . $guidance, 57],
            ['Replace concrete mixed type "mixed" in return type of anonymous function' . $guidance, 57],
        ]);
    }
}

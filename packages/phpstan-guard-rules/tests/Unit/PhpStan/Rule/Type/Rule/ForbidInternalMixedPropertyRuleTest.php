<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Type\Rule;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\Type\MixedPropertyErrorCollector;
use Toolkit\PhpStan\Rule\Type\Rule\ForbidInternalMixedPropertyRule;

/**
 * @extends RuleTestCase<ForbidInternalMixedPropertyRule>
 * @covers \Toolkit\PhpStan\Rule\Type\Rule\ForbidInternalMixedPropertyRule
 * @uses \Toolkit\PhpStan\Rule\Type\MixedPropertyErrorCollector
 * @medium
 */
#[CoversClass(ForbidInternalMixedPropertyRule::class)]
#[UsesClass(MixedPropertyErrorCollector::class)]
#[Medium]
final class ForbidInternalMixedPropertyRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidInternalMixedPropertyRule();
    }

    public function testGetNodeTypeReturnsPropertyNode(): void
    {
        self::assertSame(\PHPStan\Node\ClassPropertyNode::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeHonorsStoredPropertyVisibility(): void
    {
        $guidance = ': this declaration is internal or scope-restricted, so it must state a deterministic PHPStan type. Validate arbitrary input at an unrestricted public boundary, then pass the narrowed type inward.';
        $prefix = 'Tests\Fixture\ForbidInternalMixedType\\';
        $this->analyse([__DIR__ . '/../../../../../Fixture/ForbidInternalMixedType/ForbiddenMixedTypes.php'], [
            ['Replace concrete mixed type "array<string, mixed>" in property type of ' . $prefix . 'RestrictedTypes::$values' . $guidance, 16],
            ['Replace concrete mixed type "mixed" in property type of ' . $prefix . 'PublicContainer::$state' . $guidance, 33],
            ['Replace concrete mixed type "mixed" in property type of ' . $prefix . 'PublicContainer::$promotedState' . $guidance, 39],
            ['Replace concrete mixed type "mixed" in property type of ' . $prefix . 'MemberRestrictedTypes::$value' . $guidance, 79],
        ]);
        $this->analyse([__DIR__ . '/../../../../../Fixture/ForbidInternalMixedType/AllowedMixedTypes.php'], []);
    }
}

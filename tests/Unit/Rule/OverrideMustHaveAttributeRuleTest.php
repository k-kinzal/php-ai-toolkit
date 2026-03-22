<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PhpStanAiRules\Rule\OverrideMustHaveAttributeRule;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @extends RuleTestCase<OverrideMustHaveAttributeRule>
 */
#[CoversClass(OverrideMustHaveAttributeRule::class)]
final class OverrideMustHaveAttributeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new OverrideMustHaveAttributeRule();
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\ClassMethod::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeOverrideWithoutAttributeIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/OverrideMustHaveAttribute/WithoutAttribute.php'], [
            [
                'Override method doSomething() must have the #[\\Override] attribute.',
                16,
            ],
        ]);
    }

    public function testProcessNodeOverrideWithAttributeIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/OverrideMustHaveAttribute/WithAttribute.php'], []);
    }

    public function testProcessNodeAbstractImplementationIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/OverrideMustHaveAttribute/AbstractImpl.php'], []);
    }
}

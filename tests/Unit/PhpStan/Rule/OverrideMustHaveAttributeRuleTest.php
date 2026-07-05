<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\OverrideMustHaveAttributeRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<OverrideMustHaveAttributeRule>
 */
#[CoversClass(OverrideMustHaveAttributeRule::class)]
#[Medium]
final class OverrideMustHaveAttributeRuleTest extends RuleTestCase
{
    #[Override]
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
        $this->analyse([__DIR__ . '/../../../Fixture/OverrideMustHaveAttribute/WithoutAttribute.php'], [
            [
                'Add #[\\Override] to override method doSomething().',
                16,
            ],
        ]);
    }

    public function testProcessNodeOverrideWithAttributeIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/OverrideMustHaveAttribute/WithAttribute.php'], []);
    }

    public function testProcessNodeAbstractImplementationIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/OverrideMustHaveAttribute/AbstractImpl.php'], []);
    }
}

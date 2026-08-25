<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\NoHelperMethodInTestClassRule;
use PhpAiToolkit\PhpStan\Rule\Shared\OverrideAttributeDetector;
use PhpAiToolkit\PhpStan\Rule\Shared\TestMethodDetector;
use PhpAiToolkit\PhpStan\Rule\TestClass\OverrideMethodDetector;
use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @extends RuleTestCase<NoHelperMethodInTestClassRule>
 * @covers \PhpAiToolkit\PhpStan\Rule\NoHelperMethodInTestClassRule
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\OverrideAttributeDetector
 * @uses \PhpAiToolkit\PhpStan\Rule\TestClass\OverrideMethodDetector
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\TestMethodDetector
 */
#[CoversClass(NoHelperMethodInTestClassRule::class)]
#[UsesClass(OverrideAttributeDetector::class)]
#[UsesClass(OverrideMethodDetector::class)]
#[UsesClass(TestMethodDetector::class)]
#[Large]
final class NoHelperMethodInTestClassRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new NoHelperMethodInTestClassRule(new TestClassScope());
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\ClassMethod::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeHelperMethodIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoHelperMethodInTestClass/WithHelper.php'], [
            [
                'Move method buildUser() out of Tests\Unit\Fixture\NoHelperMethodInTestClass\WithHelper or make it a test, data provider, or framework override.',
                16,
            ],
        ]);
    }

    public function testProcessNodeOverrideAndProviderAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoHelperMethodInTestClass/CleanTestClass.php'], []);
    }
}

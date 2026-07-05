<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\NoHelperMethodInTestClassRule;
use PhpAiToolkit\PhpStan\Rule\OverrideAttributeDetector;
use PhpAiToolkit\PhpStan\Rule\OverrideMethodDetector;
use PhpAiToolkit\PhpStan\Rule\TestMethodDetector;
use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @extends RuleTestCase<NoHelperMethodInTestClassRule>
 */
#[CoversClass(NoHelperMethodInTestClassRule::class)]
#[UsesClass(OverrideAttributeDetector::class)]
#[UsesClass(OverrideMethodDetector::class)]
#[UsesClass(TestMethodDetector::class)]
#[Medium]
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
                'Method buildUser() is not an override in Tests\Unit\Fixture\NoHelperMethodInTestClass\WithHelper. Test classes should only contain test methods and framework overrides. Move helper logic to a dedicated class or inline it into the test method.',
                16,
            ],
        ]);
    }

    public function testProcessNodeOverrideAndProviderAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoHelperMethodInTestClass/CleanTestClass.php'], []);
    }
}

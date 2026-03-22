<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PhpStanAiRules\Rule\NoHelperMethodInTestClassRule;
use PhpStanAiRules\Support\TestClassScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<NoHelperMethodInTestClassRule>
 */
#[CoversClass(NoHelperMethodInTestClassRule::class)]
#[Medium]
final class NoHelperMethodInTestClassRuleTest extends RuleTestCase
{
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
        $this->analyse([__DIR__ . '/../../Fixture/NoHelperMethodInTestClass/WithHelper.php'], [
            [
                'Method buildUser() is not an override in Tests\Unit\Fixture\NoHelperMethodInTestClass\WithHelper. Test classes should only contain test methods and framework overrides. Move helper logic to a dedicated class or inline it into the test method.',
                16,
            ],
        ]);
    }

    public function testProcessNodeOverrideAndProviderAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/NoHelperMethodInTestClass/CleanTestClass.php'], []);
    }
}

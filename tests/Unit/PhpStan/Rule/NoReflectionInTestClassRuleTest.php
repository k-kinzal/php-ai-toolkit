<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\NoReflectionInTestClassRule;
use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<NoReflectionInTestClassRule>
 */
#[CoversClass(NoReflectionInTestClassRule::class)]
#[Medium]
final class NoReflectionInTestClassRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoReflectionInTestClassRule(new TestClassScope());
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Expr\New_::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeReflectionInTestClassIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoReflectionInTestClass/WithReflection.php'], [
            [
                'Using ReflectionClass in test classes is prohibited. If you need Reflection to test something, it is a sign that you are not testing behavior. Redesign the code or the test to verify observable behavior instead.',
                17,
            ],
            [
                'Using ReflectionMethod in test classes is prohibited. If you need Reflection to test something, it is a sign that you are not testing behavior. Redesign the code or the test to verify observable behavior instead.',
                23,
            ],
        ]);
    }

    public function testProcessNodeReflectionInNonTestClassIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoReflectionInTestClass/NonTestWithReflection.php'], []);
    }
}

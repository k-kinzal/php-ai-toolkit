<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use PhpStanAiRules\Rule\NoPrivateMethodInTestClassRule;
use PhpStanAiRules\Support\TestClassScope;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @extends RuleTestCase<NoPrivateMethodInTestClassRule>
 */
#[CoversClass(NoPrivateMethodInTestClassRule::class)]
final class NoPrivateMethodInTestClassRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoPrivateMethodInTestClassRule(new TestClassScope());
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\ClassMethod::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodePrivateMethodInRestrictedTestClassIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/NoPrivateMethodInTestClass/WithPrivateMethod.php'], [
            [
                'Private method helper() is prohibited in Tests\\Unit and Tests\\Integration classes. Over-abstracted helpers hide test intent and make failures harder to understand. Inline the logic into each test method, or extract to a dedicated helper class if reuse is truly needed.',
                16,
            ],
        ]);
    }

    public function testProcessNodePrivateMethodInNonTestClassIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/NoPrivateMethodInTestClass/NonTestWithPrivate.php'], []);
    }
}

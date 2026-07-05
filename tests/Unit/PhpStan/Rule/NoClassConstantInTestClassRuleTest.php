<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\NoClassConstantInTestClassRule;
use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<NoClassConstantInTestClassRule>
 */
#[CoversClass(NoClassConstantInTestClassRule::class)]
#[Medium]
final class NoClassConstantInTestClassRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new NoClassConstantInTestClassRule(new TestClassScope());
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\ClassConst::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeConstantInRestrictedTestClassIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoClassConstantInTestClass/WithConstant.php'], [
            [
                'Inline class constant FOO inside the test methods that use it. Tests\\Unit and Tests\\Integration classes must not declare constants.',
                11,
            ],
        ]);
    }

    public function testProcessNodeConstantInNonTestClassIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoClassConstantInTestClass/NonTestWithConstant.php'], []);
    }
}

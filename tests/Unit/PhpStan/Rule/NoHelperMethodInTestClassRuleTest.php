<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\NoHelperMethodInTestClassRule;
use Toolkit\PhpStan\Rule\Shared\OverrideAttributeDetector;
use Toolkit\PhpStan\Rule\Shared\TestMethodDetector;
use Toolkit\PhpStan\Rule\TestClass\OverrideMethodDetector;
use Toolkit\PhpStan\Support\TestClassScope;

/**
 * @extends RuleTestCase<NoHelperMethodInTestClassRule>
 * @covers \Toolkit\PhpStan\Rule\NoHelperMethodInTestClassRule
 * @uses \Toolkit\PhpStan\Rule\Shared\OverrideAttributeDetector
 * @uses \Toolkit\PhpStan\Rule\TestClass\OverrideMethodDetector
 * @uses \Toolkit\PhpStan\Rule\Shared\TestMethodDetector
 * @large
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

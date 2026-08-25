<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Toolkit\PhpStan\Rule\NoPrivateMethodInTestClassRule;
use Toolkit\PhpStan\Support\TestClassScope;

/**
 * @extends RuleTestCase<NoPrivateMethodInTestClassRule>
 * @covers \Toolkit\PhpStan\Rule\NoPrivateMethodInTestClassRule
 */
#[CoversClass(NoPrivateMethodInTestClassRule::class)]
#[Medium]
final class NoPrivateMethodInTestClassRuleTest extends RuleTestCase
{
    #[Override]
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
        $this->analyse([__DIR__ . '/../../../Fixture/NoPrivateMethodInTestClass/WithPrivateMethod.php'], [
            [
                'Inline private method helper() into the test method or move it to a dedicated collaborator. Tests\\Unit and Tests\\Integration classes may contain only test methods, data providers, and framework overrides.',
                16,
            ],
        ]);
    }

    public function testProcessNodePrivateMethodInNonTestClassIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoPrivateMethodInTestClass/NonTestWithPrivate.php'], []);
    }
}

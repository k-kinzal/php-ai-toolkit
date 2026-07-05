<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
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
    #[Override]
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
                'Replace ReflectionClass usage with assertions against public behavior. Test classes must not use Reflection.',
                17,
            ],
            [
                'Replace ReflectionMethod usage with assertions against public behavior. Test classes must not use Reflection.',
                23,
            ],
        ]);
    }

    public function testProcessNodeReflectionInNonTestClassIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoReflectionInTestClass/NonTestWithReflection.php'], []);
    }
}

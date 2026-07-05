<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\NoPropertyInTestClassRule;
use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<NoPropertyInTestClassRule>
 */
#[CoversClass(NoPropertyInTestClassRule::class)]
#[Medium]
final class NoPropertyInTestClassRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new NoPropertyInTestClassRule(new TestClassScope());
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\Property::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodePropertyInRestrictedTestClassIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoPropertyInTestClass/WithProperty.php'], [
            [
                'Move property $name into local variables inside the test methods that use it. Tests\\Unit and Tests\\Integration classes must not declare properties.',
                11,
            ],
        ]);
    }

    public function testProcessNodePropertyInNonTestClassIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoPropertyInTestClass/NonTestWithProperty.php'], []);
    }
}

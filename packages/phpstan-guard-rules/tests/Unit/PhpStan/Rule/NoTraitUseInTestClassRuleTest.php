<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Toolkit\PhpStan\Rule\NoTraitUseInTestClassRule;
use Toolkit\PhpStan\Support\TestClassScope;

/**
 * @extends RuleTestCase<NoTraitUseInTestClassRule>
 * @covers \Toolkit\PhpStan\Rule\NoTraitUseInTestClassRule
 * @medium
 */
#[CoversClass(NoTraitUseInTestClassRule::class)]
#[Medium]
final class NoTraitUseInTestClassRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new NoTraitUseInTestClassRule(new TestClassScope());
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\TraitUse::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeTraitUseInRestrictedTestClassIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoTraitUseInTestClass/WithTrait.php'], [
            [
                'Move trait Tests\\Unit\\Fixture\\NoTraitUseInTestClass\\HelperTrait behavior to a dedicated collaborator and call it explicitly. Tests\\Unit and Tests\\Integration classes must not use traits.',
                19,
            ],
        ]);
    }

    public function testProcessNodeTraitUseInNonTestClassIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoTraitUseInTestClass/NonTestWithTrait.php'], []);
    }
}

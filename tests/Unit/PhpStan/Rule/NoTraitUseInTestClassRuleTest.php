<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\NoTraitUseInTestClassRule;
use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<NoTraitUseInTestClassRule>
 */
#[CoversClass(NoTraitUseInTestClassRule::class)]
#[Medium]
final class NoTraitUseInTestClassRuleTest extends RuleTestCase
{
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
                'Trait Tests\\Unit\\Fixture\\NoTraitUseInTestClass\\HelperTrait is prohibited in Tests\\Unit and Tests\\Integration classes. Traits can circumvent test class restrictions (no properties, no constants, no private methods). Move shared behavior to a dedicated helper class and call it explicitly.',
                19,
            ],
        ]);
    }

    public function testProcessNodeTraitUseInNonTestClassIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoTraitUseInTestClass/NonTestWithTrait.php'], []);
    }
}

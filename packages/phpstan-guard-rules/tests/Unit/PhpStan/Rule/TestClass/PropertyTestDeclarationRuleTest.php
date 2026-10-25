<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use Override;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;
use Toolkit\PhpStan\Rule\TestClass\PropertyTestDeclarationRule;

/**
 * @extends RuleTestCase<PropertyTestDeclarationRule>
 * @covers \Toolkit\PhpStan\Rule\TestClass\PropertyTestDeclarationRule
 * @large
 */
#[CoversClass(PropertyTestDeclarationRule::class)]
#[Large]
final class PropertyTestDeclarationRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new PropertyTestDeclarationRule();
    }

    public function testGetNodeTypeIncludesUnusedTraits(): void
    {
        self::assertSame(ClassLike::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeRejectsUnusedTraitsAndInterfaces(): void
    {
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/PropertyTestConvention/tests/Property/EmptyProperties.php'], [
            ['Declare Tests\Property\EmptyFixture\SharedProperties as a concrete property test class extending PHPUnit\Framework\TestCase. Move traits, interfaces, enums, and abstract helpers out of tests/Property and Tests\Property.', 4],
            ['Declare Tests\Property\EmptyFixture\PropertyContract as a concrete property test class extending PHPUnit\Framework\TestCase. Move traits, interfaces, enums, and abstract helpers out of tests/Property and Tests\Property.', 5],
        ]);
    }

    public function testProcessNodeAcceptsFrameworkTraitsOutsidePropertyScope(): void
    {
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/PropertyTestConvention/ErisTestTrait.php'], []);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use Override;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\TestClass\PropertyTestConventionRule;
use Toolkit\PhpStan\Rule\TestClass\PropertyTestMethodValidator;
use Toolkit\PhpStan\Support\TestClassScope;

/**
 * @extends RuleTestCase<PropertyTestConventionRule>
 * @covers \Toolkit\PhpStan\Rule\TestClass\PropertyTestConventionRule
 * @uses \Toolkit\PhpStan\Rule\TestClass\PropertyTestMethodValidator
 * @uses \Toolkit\PhpStan\Support\TestClassScope
 * @large
 */
#[CoversClass(PropertyTestConventionRule::class)]
#[UsesClass(PropertyTestMethodValidator::class)]
#[UsesClass(TestClassScope::class)]
#[Large]
final class PropertyTestConventionRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new PropertyTestConventionRule(new TestClassScope());
    }

    public function testGetNodeTypeReturnsInClassNode(): void
    {
        self::assertSame(InClassNode::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeAcceptsExecutableProperties(): void
    {
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/PropertyTestConvention/tests/Property/ValidProperties.php'], []);
    }

    public function testProcessNodeRejectsMisplacedProperties(): void
    {
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/PropertyTestConvention/MisplacedProperties.php'], [
            ['Move property test class Tests\\Unit\\Fixture\\MisplacedProperties to tests/Property/MisplacedProperties.php. Property tests must be stored under tests/Property.', 7],
        ]);
    }

    public function testProcessNodeRejectsIndirectInheritance(): void
    {
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/PropertyTestConvention/tests/Property/IndirectProperties.php'], [
            ['Make property test class Tests\\Property\\Fixture\\IndirectProperties extend PHPUnit\\Framework\\TestCase directly. Move shared test behavior to collaborators.', 7],
        ]);
    }

    public function testProcessNodeRejectsInvalidClassAndMethods(): void
    {
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/PropertyTestConvention/tests/Property/InvalidTest.php'], [
            ['Rename property test class Tests\\Property\\Fixture\\InvalidTest to <Name>Properties (for example CollectionProperties) and use the same PHP filename.', 7],
            ['Make property test class Tests\\Property\\Fixture\\InvalidTest extend PHPUnit\\Framework\\TestCase directly. Move shared test behavior to collaborators.', 7],
            ['Rename Tests\\Property\\Fixture\\InvalidTest::testSomething() to satisfies<PropertyName> (for example satisfiesRoundTrip). Property test classes may declare only property test methods; move helpers, providers, and lifecycle methods out of the class.', 9],
            ['Rename Tests\\Property\\Fixture\\InvalidTest::providerValues() to satisfies<PropertyName> (for example satisfiesRoundTrip). Property test classes may declare only property test methods; move helpers, providers, and lifecycle methods out of the class.', 10],
            ['Rename Tests\\Property\\Fixture\\InvalidTest::setUp() to satisfies<PropertyName> (for example satisfiesRoundTrip). Property test classes may declare only property test methods; move helpers, providers, and lifecycle methods out of the class.', 11],
            ['Rename Tests\\Property\\Fixture\\InvalidTest::satisfies() to satisfies<PropertyName> (for example satisfiesRoundTrip). Property test classes may declare only property test methods; move helpers, providers, and lifecycle methods out of the class.', 12],
            ['Rename Tests\\Property\\Fixture\\InvalidTest::satisfieslowerCase() to satisfies<PropertyName> (for example satisfiesRoundTrip). Property test classes may declare only property test methods; move helpers, providers, and lifecycle methods out of the class.', 13],
            ['Mark Tests\\Property\\Fixture\\InvalidTest::satisfiesMissingMarker() with #[PHPUnit\\Framework\\Attributes\\Test] (PHPUnit 10+) or @test (PHPUnit 9) so PHPUnit executes this property test.', 14],
            ['Declare Tests\\Property\\Fixture\\InvalidTest::satisfiesVisibility() as a public, non-static, concrete method with no parameters. Generate inputs inside the property test.', 16],
        ]);
    }
}

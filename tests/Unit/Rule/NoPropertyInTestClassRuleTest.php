<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PhpStanAiRules\Rule\NoPropertyInTestClassRule;
use PhpStanAiRules\Support\TestClassScope;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @extends RuleTestCase<NoPropertyInTestClassRule>
 */
#[CoversClass(NoPropertyInTestClassRule::class)]
final class NoPropertyInTestClassRuleTest extends RuleTestCase
{
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
        $this->analyse([__DIR__ . '/../../Fixture/NoPropertyInTestClass/WithProperty.php'], [
            [
                'Property $name is prohibited in Tests\\Unit and Tests\\Integration classes. Shared state across test methods reduces test isolation and makes failures harder to debug. Declare values as local variables inside each test method instead.',
                11,
            ],
        ]);
    }

    public function testProcessNodePropertyInNonTestClassIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/NoPropertyInTestClass/NonTestWithProperty.php'], []);
    }
}

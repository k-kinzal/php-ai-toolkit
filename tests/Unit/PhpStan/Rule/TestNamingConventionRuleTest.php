<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\TestNamingConventionRule;
use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<TestNamingConventionRule>
 */
#[CoversClass(TestNamingConventionRule::class)]
#[Medium]
final class TestNamingConventionRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new TestNamingConventionRule(new TestClassScope());
    }


    public function testGetNodeTypeReturnsClassMethod(): void
    {
        $rule = new TestNamingConventionRule(new TestClassScope());

        self::assertSame(\PhpParser\Node\Stmt\ClassMethod::class, $rule->getNodeType());
    }


    public function testProcessNodeValidNamingIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/TestNamingConvention/ValidNaming.php'], []);
    }


    public function testProcessNodeInvalidTestMethodNamingIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/TestNamingConvention/InvalidTestNaming.php'], [
            [
                'Rename test() to test[MethodName] or test[MethodName][Behavior], e.g. testUserCanLogin().',
                11,
            ],
            [
                'Rename test method testsomething() to use PascalCase after the "test" prefix, e.g. testSomething().',
                16,
            ],
            [
                'Rename test method test_something() to use PascalCase after the "test" prefix, e.g. testSomething().',
                21,
            ],
        ]);
    }


    public function testProcessNodeInvalidProviderNamingIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/TestNamingConvention/InvalidProviderNaming.php'], [
            [
                'Rename provider() to provider[TestCaseName], e.g. providerValidEmails().',
                16,
            ],
            [
                'Rename data provider providerdata() to use PascalCase after the "provider" prefix, e.g. providerValidEmails().',
                21,
            ],
            [
                'Rename data provider provider_data() to use PascalCase after the "provider" prefix, e.g. providerValidEmails().',
                26,
            ],
        ]);
    }


    public function testProcessNodeProhibitedConstructorDestructorIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/TestNamingConvention/ProhibitedConstructorTest.php'], [
            [
                'Rename testConstruct() and test behavior through the public API instead of targeting a constructor or destructor.',
                11,
            ],
            [
                'Rename testConstructor() and test behavior through the public API instead of targeting a constructor or destructor.',
                16,
            ],
            [
                'Rename testConstructThrowsException() and test behavior through the public API instead of targeting a constructor or destructor.',
                21,
            ],
            [
                'Rename testDestruct() and test behavior through the public API instead of targeting a constructor or destructor.',
                26,
            ],
            [
                'Rename testDestructor() and test behavior through the public API instead of targeting a constructor or destructor.',
                31,
            ],
            [
                'Rename testDestructorIsCalled() and test behavior through the public API instead of targeting a constructor or destructor.',
                36,
            ],
        ]);
    }


    public function testProcessNodeNonTestClassIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/TestNamingConvention/NonTestClass.php'], []);
    }


    public function testProcessNodePublicMethodWithTestIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/TestNamingConvention/src/CoveredService.php'], []);
    }


    public function testProcessNodePublicMethodWithoutTestIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/TestNamingConvention/src/UncoveredService.php'], [
            [
                'Add a unit test method starting with testGetResult() for public method getResult().',
                13,
            ],
        ]);
    }
}

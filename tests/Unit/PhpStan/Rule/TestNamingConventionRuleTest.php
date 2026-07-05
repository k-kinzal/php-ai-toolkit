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
                'Test method test() must follow the pattern test[MethodName] or test[MethodName][Behavior]. The prefix "test" alone is not a valid name. Example: testUserCanLogin().',
                11,
            ],
            [
                'Test method testsomething() does not follow the naming convention. After the "test" prefix, the next character must be an uppercase letter (PascalCase). Example: testSomething(), testUserCanLogin().',
                16,
            ],
            [
                'Test method test_something() does not follow the naming convention. After the "test" prefix, the next character must be an uppercase letter (PascalCase). Example: testSomething(), testUserCanLogin().',
                21,
            ],
        ]);
    }


    public function testProcessNodeInvalidProviderNamingIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/TestNamingConvention/InvalidProviderNaming.php'], [
            [
                'Data provider provider() must follow the pattern provider[TestCaseName]. The prefix "provider" alone is not a valid name. Example: providerValidEmails().',
                16,
            ],
            [
                'Data provider providerdata() does not follow the naming convention. After the "provider" prefix, the next character must be an uppercase letter (PascalCase). Example: providerValidEmails(), providerUserData().',
                21,
            ],
            [
                'Data provider provider_data() does not follow the naming convention. After the "provider" prefix, the next character must be an uppercase letter (PascalCase). Example: providerValidEmails(), providerUserData().',
                26,
            ],
        ]);
    }


    public function testProcessNodeProhibitedConstructorDestructorIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/TestNamingConvention/ProhibitedConstructorTest.php'], [
            [
                'Test method testConstruct() tests a constructor or destructor directly. Constructors and destructors are implementation details; test the resulting behavior through the public API instead.',
                11,
            ],
            [
                'Test method testConstructor() tests a constructor or destructor directly. Constructors and destructors are implementation details; test the resulting behavior through the public API instead.',
                16,
            ],
            [
                'Test method testConstructThrowsException() tests a constructor or destructor directly. Constructors and destructors are implementation details; test the resulting behavior through the public API instead.',
                21,
            ],
            [
                'Test method testDestruct() tests a constructor or destructor directly. Constructors and destructors are implementation details; test the resulting behavior through the public API instead.',
                26,
            ],
            [
                'Test method testDestructor() tests a constructor or destructor directly. Constructors and destructors are implementation details; test the resulting behavior through the public API instead.',
                31,
            ],
            [
                'Test method testDestructorIsCalled() tests a constructor or destructor directly. Constructors and destructors are implementation details; test the resulting behavior through the public API instead.',
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
                'Public method getResult() has no corresponding test method starting with testGetResult() in the unit test file. Each public method must have at least one test that verifies its behavior.',
                13,
            ],
        ]);
    }
}

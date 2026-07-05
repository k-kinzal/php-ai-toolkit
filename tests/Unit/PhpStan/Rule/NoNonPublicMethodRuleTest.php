<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\NoNonPublicMethodRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<NoNonPublicMethodRule>
 */
#[CoversClass(NoNonPublicMethodRule::class)]
#[Medium]
final class NoNonPublicMethodRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new NoNonPublicMethodRule();
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\ClassLike::class, $this->getRule()->getNodeType());
    }

    public function testPrivateMethodViolationIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoNonPublicMethod/WithPrivateMethod.php'], [
            [
                'Private method helper() is prohibited in Tests\\Fixture\\NoNonPublicMethod\\WithPrivateMethod. Private behavior hides a responsibility inside the class; extract that behavior to a focused collaborator with a public API, or make it public only when it is part of this type\'s own responsibility.',
                14,
            ],
        ]);
    }

    public function testProtectedMethodViolationIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoNonPublicMethod/WithProtectedMethod.php'], [
            [
                'Protected method helper() is prohibited in concrete class Tests\\Fixture\\NoNonPublicMethod\\WithProtectedMethod. Protected methods are allowed only in abstract classes, traits, or override methods. Extract the behavior to a focused collaborator, or move the extension point to an abstract class or trait if inheritance is intentional.',
                14,
            ],
        ]);
    }

    public function testAllowsProtectedMethodInAbstractClass(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoNonPublicMethod/AbstractClassWithProtectedMethod.php'], []);
    }

    public function testProcessNodeProtectedMethodInTraitIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoNonPublicMethod/TraitWithProtectedMethod.php'], []);
    }

    public function testHasOverrideAttributeAllowsProtectedOverride(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoNonPublicMethod/OverrideProtectedMethod.php'], []);
    }

    public function testResolveClassNameInPrivateMethodViolationForAbstractClass(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoNonPublicMethod/AbstractClassWithPrivateMethod.php'], [
            [
                'Private method helper() is prohibited in Tests\\Fixture\\NoNonPublicMethod\\AbstractClassWithPrivateMethod. Private behavior hides a responsibility inside the class; extract that behavior to a focused collaborator with a public API, or make it public only when it is part of this type\'s own responsibility.',
                14,
            ],
        ]);
    }

    public function testProcessNodePrivateMethodInTraitIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoNonPublicMethod/TraitWithPrivateMethod.php'], [
            [
                'Private method helper() is prohibited in Tests\\Fixture\\NoNonPublicMethod\\TraitWithPrivateMethod. Private behavior hides a responsibility inside the class; extract that behavior to a focused collaborator with a public API, or make it public only when it is part of this type\'s own responsibility.',
                14,
            ],
        ]);
    }
}

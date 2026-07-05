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
                'Move private method helper() out of Tests\\Fixture\\NoNonPublicMethod\\WithPrivateMethod into a focused collaborator, or make it public only if it is part of this type\'s API.',
                14,
            ],
        ]);
    }

    public function testProtectedMethodViolationIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoNonPublicMethod/WithProtectedMethod.php'], [
            [
                'Move protected method helper() out of concrete class Tests\\Fixture\\NoNonPublicMethod\\WithProtectedMethod, or put the extension point on an abstract class, trait, or override method.',
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
                'Move private method helper() out of Tests\\Fixture\\NoNonPublicMethod\\AbstractClassWithPrivateMethod into a focused collaborator, or make it public only if it is part of this type\'s API.',
                14,
            ],
        ]);
    }

    public function testProcessNodePrivateMethodInTraitIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoNonPublicMethod/TraitWithPrivateMethod.php'], [
            [
                'Move private method helper() out of Tests\\Fixture\\NoNonPublicMethod\\TraitWithPrivateMethod into a focused collaborator, or make it public only if it is part of this type\'s API.',
                14,
            ],
        ]);
    }
}

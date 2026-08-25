<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ClassDesign;

use Override;
use PhpAiToolkit\PhpStan\Rule\ClassDesign\ForbiddenMagicMethodCallRule;
use PhpAiToolkit\PhpStan\Rule\ClassDesign\MagicMethodCallErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\ClassDesign\MagicMethodCallInspector;
use PhpAiToolkit\PhpStan\Rule\ClassDesign\MagicMethodRegistry;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @extends RuleTestCase<ForbiddenMagicMethodCallRule>
 * @covers \PhpAiToolkit\PhpStan\Rule\ClassDesign\ForbiddenMagicMethodCallRule
 * @uses \PhpAiToolkit\PhpStan\Rule\ClassDesign\MagicMethodCallErrorBuilder
 * @uses \PhpAiToolkit\PhpStan\Rule\ClassDesign\MagicMethodCallInspector
 * @uses \PhpAiToolkit\PhpStan\Rule\ClassDesign\MagicMethodRegistry
 */
#[CoversClass(ForbiddenMagicMethodCallRule::class)]
#[UsesClass(MagicMethodCallErrorBuilder::class)]
#[UsesClass(MagicMethodCallInspector::class)]
#[UsesClass(MagicMethodRegistry::class)]
#[Medium]
final class ForbiddenMagicMethodCallRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbiddenMagicMethodCallRule();
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Expr::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeDirectMagicMethodCallIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/ForbiddenMagicMethodCall/DirectCall.php'], [
            [
                'Use (string) cast: (string)$obj instead of calling __toString() directly.',
                19,
            ],
        ]);
    }

    public function testProcessNodeParentStaticCallIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/ForbiddenMagicMethodCall/ParentCall.php'], []);
    }

    public function testProcessNodeNormalMethodCallIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/ForbiddenMagicMethodCall/NormalCall.php'], []);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\ForbiddenMagicMethodCallRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<ForbiddenMagicMethodCallRule>
 */
#[CoversClass(ForbiddenMagicMethodCallRule::class)]
#[Medium]
final class ForbiddenMagicMethodCallRuleTest extends RuleTestCase
{
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
        $this->analyse([__DIR__ . '/../../../Fixture/ForbiddenMagicMethodCall/DirectCall.php'], [
            [
                'Direct call to magic method __toString() is prohibited. Magic methods are invoked implicitly by PHP; calling them directly bypasses language semantics. Use (string) cast: (string)$obj.',
                19,
            ],
        ]);
    }

    public function testProcessNodeParentStaticCallIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbiddenMagicMethodCall/ParentCall.php'], []);
    }

    public function testProcessNodeNormalMethodCallIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbiddenMagicMethodCall/NormalCall.php'], []);
    }
}

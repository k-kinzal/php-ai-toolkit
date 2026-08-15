<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\RequireThrowsTagOnDirectThrow\ThrowsDeclarationInspector;
use PhpAiToolkit\PhpStan\Rule\RequireThrowsTagOnDirectThrow\ThrowSite;
use PhpAiToolkit\PhpStan\Rule\RequireThrowsTagOnDirectThrow\ThrowSiteCollector;
use PhpAiToolkit\PhpStan\Rule\RequireThrowsTagOnDirectThrow\ThrowSiteVisitor;
use PhpAiToolkit\PhpStan\Rule\RequireThrowsTagOnDirectThrowRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @extends RuleTestCase<RequireThrowsTagOnDirectThrowRule>
 */
#[CoversClass(RequireThrowsTagOnDirectThrowRule::class)]
#[UsesClass(ThrowSite::class)]
#[UsesClass(ThrowSiteCollector::class)]
#[UsesClass(ThrowSiteVisitor::class)]
#[UsesClass(ThrowsDeclarationInspector::class)]
#[Medium]
final class RequireThrowsTagOnDirectThrowRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new RequireThrowsTagOnDirectThrowRule();
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\ClassMethod::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeUndeclaredThrowsAreReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequireThrowsTagOnDirectThrow/WithUndeclaredThrow.php'], [
            [
                'Declare "@throws \RuntimeException" in the PHPDoc of withoutTag() or catch the exception inside the method. The exception thrown here escapes withoutTag() without being declared.',
                14,
            ],
            [
                'Declare "@throws \RuntimeException" in the PHPDoc of withWrongTag() or catch the exception inside the method. The exception thrown here escapes withWrongTag() without being declared.',
                22,
            ],
            [
                'Declare "@throws \RuntimeException" in the PHPDoc of withMismatchedCatch() or catch the exception inside the method. The exception thrown here escapes withMismatchedCatch() without being declared.',
                28,
            ],
            [
                'Declare "@throws \RuntimeException" in the PHPDoc of withUndeclaredRethrow() or catch the exception inside the method. The exception thrown here escapes withUndeclaredRethrow() without being declared.',
                39,
            ],
        ]);
    }

    public function testProcessNodeDeclaredOrCaughtThrowsAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequireThrowsTagOnDirectThrow/WithDeclaredOrCaughtThrow.php'], []);
    }
}

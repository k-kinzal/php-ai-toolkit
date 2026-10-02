<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Extension\RedundantDiagnostic;

use Exception;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Extension\RedundantDiagnostic\DirectThrowDiagnosticPolicy;
use Toolkit\PhpStan\Rule\ExceptionHandling\ThrowSite;
use Toolkit\PhpStan\Rule\ExceptionHandling\ThrowSiteCollector;
use Toolkit\PhpStan\Rule\ExceptionHandling\ThrowSiteVisitor;
use Toolkit\PhpStan\Rule\Shared\ThrownExpression;

/**
 * @covers \Toolkit\PhpStan\Extension\RedundantDiagnostic\DirectThrowDiagnosticPolicy
 * @uses \Toolkit\PhpStan\Rule\ExceptionHandling\ThrowSite
 * @uses \Toolkit\PhpStan\Rule\ExceptionHandling\ThrowSiteCollector
 * @uses \Toolkit\PhpStan\Rule\ExceptionHandling\ThrowSiteVisitor
 * @uses \Toolkit\PhpStan\Rule\Shared\ThrownExpression
 */
#[CoversClass(DirectThrowDiagnosticPolicy::class)]
#[UsesClass(ThrowSite::class)]
#[UsesClass(ThrowSiteCollector::class)]
#[UsesClass(ThrowSiteVisitor::class)]
#[UsesClass(ThrownExpression::class)]
final class DirectThrowDiagnosticPolicyTest extends TestCase
{
    public function testIsRedundantRecognizesDirectCheckedExceptionDiagnostic(): void
    {
        $throw = new Throw_(new New_(new Name(Exception::class)), ['startLine' => 12]);
        $method = new ClassMethod('run', [
            'stmts' => [new Expression($throw)],
        ]);

        self::assertTrue((new DirectThrowDiagnosticPolicy())->isRedundant(
            'missingType.checkedException',
            12,
            $method,
        ));
    }

    public function testPropagatedAndUnrelatedDiagnosticsRemainVisible(): void
    {
        $method = new ClassMethod('run', ['stmts' => [new Nop()]]);
        $policy = new DirectThrowDiagnosticPolicy();

        self::assertFalse($policy->isRedundant('missingType.checkedException', 12, $method));
        self::assertFalse($policy->isRedundant('return.type', 12, $method));
        self::assertFalse($policy->isRedundant('missingType.checkedException', null, $method));
        self::assertFalse($policy->isRedundant('missingType.checkedException', 12, new Nop()));
    }

    public function testStatementsReturnsEmptyForAnUnrelatedNode(): void
    {
        self::assertSame([], (new DirectThrowDiagnosticPolicy())->statements(new Nop()));
    }
}

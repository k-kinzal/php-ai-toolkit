<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ExceptionHandling;

use PhpAiToolkit\PhpStan\Rule\ExceptionHandling\ThrowChainEvaluator;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ThrowChainEvaluator::class)]
final class ThrowChainEvaluatorTest extends TestCase
{
    public function testViolatesReportsNewExceptionWithoutCaughtVariable(): void
    {
        $throw = new Throw_(new New_(new Name('DomainException'), [new Arg(new String_('wrapped'))]));

        self::assertTrue((new ThrowChainEvaluator())->violates($throw, 'exception'));
    }

    public function testViolatesReportsAnyNewExceptionInNonBindingCatch(): void
    {
        $throw = new Throw_(new New_(new Name('DomainException'), [new Arg(new Variable('other'))]));

        self::assertTrue((new ThrowChainEvaluator())->violates($throw, null));
    }

    public function testViolatesAcceptsNewExceptionReferencingCaughtVariable(): void
    {
        $throw = new Throw_(new New_(new Name('DomainException'), [
            new Arg(new String_('wrapped')),
            new Arg(new Variable('exception')),
        ]));

        self::assertFalse((new ThrowChainEvaluator())->violates($throw, 'exception'));
    }

    public function testViolatesAcceptsPlainRethrow(): void
    {
        $throw = new Throw_(new Variable('exception'));

        self::assertFalse((new ThrowChainEvaluator())->violates($throw, 'exception'));
    }
}

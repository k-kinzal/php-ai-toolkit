<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ExceptionHandling;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Toolkit\PhpStan\Rule\ExceptionHandling\ForbidEmptyCatchRule;

/**
 * @extends RuleTestCase<ForbidEmptyCatchRule>
 * @covers \Toolkit\PhpStan\Rule\ExceptionHandling\ForbidEmptyCatchRule
 * @medium
 */
#[CoversClass(ForbidEmptyCatchRule::class)]
#[Medium]
final class ForbidEmptyCatchRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidEmptyCatchRule();
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\Catch_::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeEmptyCatchIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/ForbidEmptyCatch/WithEmptyCatch.php'], [
            [
                'Handle the caught RuntimeException in this empty catch block: rethrow it, wrap it in a more specific exception with $previous, or log it and recover. An empty catch silently discards the failure.',
                16,
            ],
            [
                'Handle the caught Throwable in this empty catch block: rethrow it, wrap it in a more specific exception with $previous, or log it and recover. An empty catch silently discards the failure.',
                26,
            ],
        ]);
    }

    public function testProcessNodeHandledCatchIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/ForbidEmptyCatch/WithHandledCatch.php'], []);
    }
}

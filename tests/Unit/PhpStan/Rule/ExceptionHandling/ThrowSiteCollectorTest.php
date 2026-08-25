<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ExceptionHandling;

use PhpAiToolkit\PhpStan\Rule\ExceptionHandling\ThrowSiteCollector;
use PhpAiToolkit\PhpStan\Rule\Shared\ThrownExpression;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\ExceptionHandling\ThrowSiteCollector
 * @uses \PhpAiToolkit\PhpStan\Rule\ExceptionHandling\ThrowSite
 * @uses \PhpAiToolkit\PhpStan\Rule\ExceptionHandling\ThrowSiteVisitor
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\ThrownExpression
 */
#[CoversClass(ThrowSiteCollector::class)]
#[UsesClass(\PhpAiToolkit\PhpStan\Rule\ExceptionHandling\ThrowSite::class)]
#[UsesClass(\PhpAiToolkit\PhpStan\Rule\ExceptionHandling\ThrowSiteVisitor::class)]
#[UsesClass(ThrownExpression::class)]
final class ThrowSiteCollectorTest extends TestCase
{
    public function testCollectReturnsUncaughtThrowsWithGuards(): void
    {
        $statements = (new ParserFactory())->createForHostVersion()->parse(
            '<?php try { throw new RuntimeException("a"); } catch (LogicException $e) { throw $e; } throw new DomainException("b");'
        );
        self::assertNotNull($statements);

        $sites = (new ThrowSiteCollector())->collect($statements);

        self::assertCount(3, $sites);
        self::assertSame('RuntimeException', $sites[0]->thrownNames[0]->toString());
        self::assertSame(['LogicException'], [$sites[0]->guardNames[0]->toString()]);
        self::assertSame('LogicException', $sites[1]->thrownNames[0]->toString());
        self::assertSame([], $sites[1]->guardNames);
        self::assertSame('DomainException', $sites[2]->thrownNames[0]->toString());
        self::assertSame([], $sites[2]->guardNames);
    }

    public function testCollectSkipsThrowsInNestedFunctionScopes(): void
    {
        $statements = (new ParserFactory())->createForHostVersion()->parse(
            '<?php $callback = function () { throw new RuntimeException("a"); }; $arrow = fn () => throw new RuntimeException("b");'
        );
        self::assertNotNull($statements);

        self::assertSame([], (new ThrowSiteCollector())->collect($statements));
    }

    public function testCollectSkipsDynamicThrows(): void
    {
        $statements = (new ParserFactory())->createForHostVersion()->parse(
            '<?php throw $exception;'
        );
        self::assertNotNull($statements);

        self::assertSame([], (new ThrowSiteCollector())->collect($statements));
    }
}

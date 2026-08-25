<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ExceptionHandling;

use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ExceptionHandling\CatchThrowCollector;
use Toolkit\PhpStan\Rule\Shared\ThrownExpression;

/**
 * @covers \Toolkit\PhpStan\Rule\ExceptionHandling\CatchThrowCollector
 * @uses \Toolkit\PhpStan\Rule\ExceptionHandling\CatchThrowVisitor
 * @uses \Toolkit\PhpStan\Rule\Shared\ThrownExpression
 */
#[CoversClass(CatchThrowCollector::class)]
#[UsesClass(\Toolkit\PhpStan\Rule\ExceptionHandling\CatchThrowVisitor::class)]
#[UsesClass(ThrownExpression::class)]
final class CatchThrowCollectorTest extends TestCase
{
    public function testCollectReturnsDirectThrowsOnly(): void
    {
        $statements = (new ParserFactory())->createForHostVersion()->parse(
            '<?php throw new DomainException("direct"); try { throw new RuntimeException("nested"); } catch (RuntimeException $e) { throw $e; } $f = function () { throw new LogicException("closure"); };'
        );
        self::assertNotNull($statements);

        $throws = (new CatchThrowCollector())->collect($statements);

        self::assertCount(1, $throws);
    }
}

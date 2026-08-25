<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ExceptionHandling;

use PhpAiToolkit\PhpStan\Rule\ExceptionHandling\CatchThrowCollector;
use PhpAiToolkit\PhpStan\Rule\Shared\ThrownExpression;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\ExceptionHandling\CatchThrowCollector
 * @uses \PhpAiToolkit\PhpStan\Rule\ExceptionHandling\CatchThrowVisitor
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\ThrownExpression
 */
#[CoversClass(CatchThrowCollector::class)]
#[UsesClass(\PhpAiToolkit\PhpStan\Rule\ExceptionHandling\CatchThrowVisitor::class)]
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

<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\RequireExceptionChaining;

use PhpAiToolkit\PhpStan\Rule\RequireExceptionChaining\CatchThrowCollector;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CatchThrowCollector::class)]
#[UsesClass(\PhpAiToolkit\PhpStan\Rule\RequireExceptionChaining\CatchThrowVisitor::class)]
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

<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Diagnostic;

use Guard\Policy\Diagnostic\MetricViolation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Diagnostic\MetricViolation
 */
#[CoversClass(MetricViolation::class)]
final class MetricViolationTest extends TestCase
{
    public function testStoresViolationData(): void
    {
        $violation = new MetricViolation('src/Example.php', 12, 'method_lines', 51, 50, 'Too long.', 'native-api');

        self::assertSame('src/Example.php', $violation->path);
        self::assertSame(12, $violation->line);
        self::assertSame('method_lines', $violation->rule);
        self::assertSame(51, $violation->actual);
        self::assertSame(50, $violation->limit);
        self::assertSame('Too long.', $violation->message);
        self::assertSame('native-api', $violation->policy);
    }
}

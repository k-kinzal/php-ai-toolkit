<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Loc;

use Guard\Policy\Loc\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Loc\Violation
 */
#[CoversClass(Violation::class)]
final class ViolationTest extends TestCase
{
    public function testStoresViolationData(): void
    {
        $violation = new Violation('src/Example.php', 12, 'method_lines', 51, 50, 'Too long.', 'native-api');

        self::assertSame('src/Example.php', $violation->path);
        self::assertSame(12, $violation->line);
        self::assertSame('method_lines', $violation->rule);
        self::assertSame(51, $violation->actual);
        self::assertSame(50, $violation->limit);
        self::assertSame('Too long.', $violation->message);
        self::assertSame('native-api', $violation->policy);
    }
}

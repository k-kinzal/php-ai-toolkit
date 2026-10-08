<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

/**
 * @covers \Guard\Reporting\BaselineMatch
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Guard\Reporting\BaselineMatch::class)]
final class BaselineMatchTest extends \PHPUnit\Framework\TestCase
{
    public function testKeepsSuppressionAndStaleCountsSeparate(): void
    {
        $match = new \Guard\Reporting\BaselineMatch([], 2, 3);
        self::assertSame([], $match->findings);
        self::assertSame(2, $match->suppressed);
        self::assertSame(3, $match->unmatched);
    }
}

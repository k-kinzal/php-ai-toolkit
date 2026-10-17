<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use Guard\Reporting\BaselineMatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Reporting\BaselineMatch
 */
#[CoversClass(BaselineMatch::class)]
final class BaselineMatchTest extends TestCase
{
    public function testKeepsSuppressionAndStaleCountsSeparate(): void
    {
        $match = new BaselineMatch([], 2, 3);
        self::assertSame([], $match->findings);
        self::assertSame(2, $match->suppressed);
        self::assertSame(3, $match->unmatched);
    }
}

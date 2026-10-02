<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Policy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Policy\PolicyException
 */
#[CoversClass(\Toolkit\Guard\Policy\PolicyException::class)]
final class PolicyExceptionTest extends TestCase
{
    public function testCarriesActionableContext(): void
    {
        $error = new \Toolkit\Guard\Policy\PolicyException('configuration.limit: choose a numeric repair.');
        self::assertSame('configuration.limit: choose a numeric repair.', $error->getMessage());
    }

}

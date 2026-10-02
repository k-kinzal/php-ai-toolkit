<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Cli\LocGuardCliValueOption;

/**
 * @covers \Toolkit\LocGuard\Cli\LocGuardCliValueOption
 */
#[CoversClass(LocGuardCliValueOption::class)]
final class LocGuardCliValueOptionTest extends TestCase
{
    public function testStoresNormalizedOptionValue(): void
    {
        $option = new LocGuardCliValueOption('config', 'strict.yaml', true);

        self::assertSame('config', $option->key);
        self::assertSame('strict.yaml', $option->value);
        self::assertTrue($option->consumesNext);
    }
}

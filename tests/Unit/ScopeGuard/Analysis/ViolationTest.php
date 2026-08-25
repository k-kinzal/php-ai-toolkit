<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Analysis\Violation;

/**
 * @covers \Toolkit\ScopeGuard\Analysis\Violation
 */
#[CoversClass(Violation::class)]
final class ViolationTest extends TestCase
{
    /**
     * @dataProvider providerViolation
     */
    #[DataProvider('providerViolation')]
    public function testPathIsReadable(Violation $violation): void
    {
        self::assertSame('src/Http/Controller.php', $violation->path);
    }

    /**
     * @dataProvider providerViolation
     */
    #[DataProvider('providerViolation')]
    public function testLineIsReadable(Violation $violation): void
    {
        self::assertSame(21, $violation->line);
    }

    /**
     * @dataProvider providerViolation
     */
    #[DataProvider('providerViolation')]
    public function testRuleIsReadable(Violation $violation): void
    {
        self::assertSame('out_of_scope', $violation->rule);
    }

    /**
     * @dataProvider providerViolation
     */
    #[DataProvider('providerViolation')]
    public function testSymbolIsReadable(Violation $violation): void
    {
        self::assertSame('App\\Domain\\Order', $violation->symbol);
    }

    /**
     * @dataProvider providerViolation
     */
    #[DataProvider('providerViolation')]
    public function testMessageIsReadable(Violation $violation): void
    {
        self::assertSame('Not visible.', $violation->message);
    }


    /**
     * @return array<string, array{Violation}>
     */
    public static function providerViolation(): array
    {
        return ['one out of scope violation' => [
            new Violation('src/Http/Controller.php', 21, 'out_of_scope', 'App\\Domain\\Order', 'Not visible.'),
        ]];
    }
}

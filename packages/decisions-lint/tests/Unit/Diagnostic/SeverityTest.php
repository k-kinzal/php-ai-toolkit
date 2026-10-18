<?php

declare(strict_types=1);

namespace Tests\Unit\Diagnostic;

use DecisionsLint\Diagnostic\Severity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Diagnostic\Severity
 */
#[CoversClass(Severity::class)]
final class SeverityTest extends TestCase
{
    public function testRankOrdersErrorAboveWarningAboveInfo(): void
    {
        $severity = new Severity();
        self::assertSame(3, $severity->rank(Severity::ERROR));
        self::assertSame(2, $severity->rank(Severity::WARNING));
        self::assertSame(1, $severity->rank(Severity::INFO));
        self::assertSame(0, $severity->rank('never'));
    }

    /**
     * @dataProvider providerAtLeast
     */
    #[DataProvider('providerAtLeast')]
    public function testAtLeastComparesAgainstAMinimum(string $severity, string $minimum, bool $expected): void
    {
        self::assertSame($expected, (new Severity())->atLeast($severity, $minimum));
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function providerAtLeast(): iterable
    {
        yield 'error reaches error' => [Severity::ERROR, Severity::ERROR, true];
        yield 'warning does not reach error' => [Severity::WARNING, Severity::ERROR, false];
        yield 'error reaches info' => [Severity::ERROR, Severity::INFO, true];
        yield 'nothing reaches never' => [Severity::ERROR, 'never', false];
    }
}

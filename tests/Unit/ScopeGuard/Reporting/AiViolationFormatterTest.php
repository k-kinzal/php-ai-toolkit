<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Analysis\Violation;
use Toolkit\ScopeGuard\Reporting\AiViolationAction;
use Toolkit\ScopeGuard\Reporting\AiViolationFormatter;

/**
 * @covers \Toolkit\ScopeGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\ScopeGuard\Reporting\AiViolationAction
 * @uses \Toolkit\ScopeGuard\Analysis\Violation
 */
#[CoversClass(AiViolationFormatter::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(Violation::class)]
final class AiViolationFormatterTest extends TestCase
{
    public function testFormatOpensWithTheNumberedLocation(): void
    {
        $violation = new Violation('src/A.php', 21, 'out_of_scope', 'App\\A', 'Not visible.');

        self::assertStringStartsWith("1. src/A.php:21 [out_of_scope]\n", (new AiViolationFormatter())->format(1, $violation));
    }

    public function testFormatIncludesTheSymbolAndMessage(): void
    {
        $violation = new Violation('src/A.php', 21, 'out_of_scope', 'App\\A', 'Not visible.');

        self::assertStringContainsString("   symbol: App\\A\n   message: Not visible.\n", (new AiViolationFormatter())->format(1, $violation));
    }

    public function testFormatEndsWithAnAction(): void
    {
        $violation = new Violation('src/A.php', 21, 'out_of_scope', 'App\\A', 'Not visible.');

        self::assertStringEndsWith(".\n", (new AiViolationFormatter())->format(1, $violation));
    }
}

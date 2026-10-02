<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\Violation;
use Toolkit\LocGuard\Reporting\AiViolationAction;
use Toolkit\LocGuard\Reporting\AiViolationFormatter;

/**
 * @covers \Toolkit\LocGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\LocGuard\Reporting\AiViolationAction
 * @uses \Toolkit\LocGuard\Analysis\Violation
 */
#[CoversClass(AiViolationFormatter::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(Violation::class)]
final class AiViolationFormatterTest extends TestCase
{
    public function testFormatReturnsNumberedViolationBlock(): void
    {
        $block = (new AiViolationFormatter())->format(2, new Violation('src/A.php', 3, 'file_lines', 10, 5, 'Large.', 'strict'));

        self::assertStringContainsString('2. src/A.php:3 [file_lines]', $block);
        self::assertStringContainsString('policy: strict', $block);
        self::assertStringContainsString('action:', $block);
    }
}

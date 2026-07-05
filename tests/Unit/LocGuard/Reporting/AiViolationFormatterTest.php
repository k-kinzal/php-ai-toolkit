<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Reporting\AiViolationAction;
use PhpAiToolkit\LocGuard\Reporting\AiViolationFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AiViolationFormatter::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(Violation::class)]
final class AiViolationFormatterTest extends TestCase
{
    public function testFormatReturnsNumberedViolationBlock(): void
    {
        $block = (new AiViolationFormatter())->format(2, new Violation('src/A.php', 3, 'file_lines', 10, 5, 'Large.'));

        self::assertStringContainsString('2. src/A.php:3 [file_lines]', $block);
        self::assertStringContainsString('action:', $block);
    }
}

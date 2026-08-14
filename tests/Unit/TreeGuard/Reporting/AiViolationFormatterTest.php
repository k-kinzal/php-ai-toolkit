<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Reporting;

use PhpAiToolkit\TreeGuard\Analysis\Violation;
use PhpAiToolkit\TreeGuard\Reporting\AiViolationAction;
use PhpAiToolkit\TreeGuard\Reporting\AiViolationFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AiViolationFormatter::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(Violation::class)]
final class AiViolationFormatterTest extends TestCase
{
    public function testFormatIncludesActualAndLimit(): void
    {
        $violation = new Violation('src/A', 'max_files', 'src/*', 26, 25, 'Too many files.');

        $block = (new AiViolationFormatter())->format(1, $violation);

        self::assertStringContainsString("1. src/A [max_files]\n", $block);
        self::assertStringContainsString("   pattern: src/*\n", $block);
        self::assertStringContainsString("   actual: 26\n", $block);
        self::assertStringContainsString("   limit: 25\n", $block);
        self::assertStringContainsString("   message: Too many files.\n", $block);
        self::assertStringContainsString('   action: ', $block);
    }

    public function testFormatOmitsNullActualAndLimit(): void
    {
        $violation = new Violation('src/notes.txt', 'disallowed_file', 'src/**', null, null, 'Not allowed.');

        $block = (new AiViolationFormatter())->format(2, $violation);

        self::assertStringContainsString("2. src/notes.txt [disallowed_file]\n", $block);
        self::assertStringNotContainsString('actual:', $block);
        self::assertStringNotContainsString('limit:', $block);
        self::assertStringContainsString("   message: Not allowed.\n", $block);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Cli;

use PhpAiToolkit\Doctest\Cli\DoctestHelpText;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DoctestHelpText::class)]
final class DoctestHelpTextTest extends TestCase
{
    public function testTextDocumentsEverySupportedOption(): void
    {
        $text = (new DoctestHelpText())->text();

        self::assertStringStartsWith('doctest runs the examples written in PHPDoc blocks.', $text);
        self::assertStringContainsString('--config PATH', $text);
        self::assertStringContainsString('--filter ID', $text);
        self::assertStringContainsString('--list', $text);
        self::assertStringContainsString('--reporter NAME', $text);
        self::assertStringContainsString('--format NAME', $text);
        self::assertStringContainsString('--help, -h', $text);
        self::assertStringContainsString('--version, -V', $text);
    }
}

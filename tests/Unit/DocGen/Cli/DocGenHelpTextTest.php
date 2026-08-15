<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PhpAiToolkit\DocGen\Cli\DocGenHelpText;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocGenHelpText::class)]
final class DocGenHelpTextTest extends TestCase
{
    public function testTextDescribesUsageLine(): void
    {
        self::assertStringContainsString('Usage: doc-gen', (new DocGenHelpText())->text());
    }

    public function testTextListsEveryOption(): void
    {
        $text = (new DocGenHelpText())->text();

        self::assertStringContainsString('--config', $text);
        self::assertStringContainsString('--output', $text);
        self::assertStringContainsString('--vendor[=GLOBS]', $text);
        self::assertStringContainsString('--vendor-dev[=GLOBS]', $text);
        self::assertStringContainsString('--coverage', $text);
        self::assertStringContainsString('--serve', $text);
        self::assertStringContainsString('-h, --help', $text);
        self::assertStringContainsString('-V, --version', $text);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Cli\DocGuardHelpText;

/**
 * @covers \Toolkit\DocGuard\Cli\DocGuardHelpText
 */
#[CoversClass(DocGuardHelpText::class)]
final class DocGuardHelpTextTest extends TestCase
{
    public function testTextDescribesUsageAndOptions(): void
    {
        $text = (new DocGuardHelpText())->text();

        self::assertStringContainsString('doc-guard [--config=doc-guard.yaml] [--reporter=ai|text|json]', $text);
        self::assertStringContainsString('doc-guard --generate [PATH...]', $text);
        self::assertStringContainsString('--version, -V', $text);
    }
}

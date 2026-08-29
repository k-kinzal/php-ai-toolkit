<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Cli\LocGuardCliArgumentParser;
use Toolkit\LocGuard\Cli\LocGuardCliValueOption;
use Toolkit\LocGuard\Cli\LocGuardCliValueOptionParser;
use Toolkit\LocGuard\LocGuardException;

/**
 * @covers \Toolkit\LocGuard\Cli\LocGuardCliArgumentParser
 * @uses \Toolkit\LocGuard\Cli\LocGuardCliValueOption
 * @uses \Toolkit\LocGuard\Cli\LocGuardCliValueOptionParser
 */
#[CoversClass(LocGuardCliArgumentParser::class)]
#[UsesClass(LocGuardCliValueOption::class)]
#[UsesClass(LocGuardCliValueOptionParser::class)]
final class LocGuardCliArgumentParserTest extends TestCase
{
    public function testParseReturnsConfigReporterAndFlags(): void
    {
        self::assertSame([
            'config' => 'custom.yaml',
            'explain' => null,
            'help' => true,
            'reporter' => 'json',
            'version' => false,
        ], (new LocGuardCliArgumentParser())->parse(['--config', 'custom.yaml', '--reporter=json', '--help']));
    }

    public function testParseRejectsMissingOptionValue(): void
    {
        $this->expectException(LocGuardException::class);

        (new LocGuardCliArgumentParser())->parse(['--format']);
    }

    public function testParseReturnsExplainPath(): void
    {
        self::assertSame('src/Example.php', (new LocGuardCliArgumentParser())->parse([
            '--explain=src/Example.php',
        ])['explain']);
    }
}

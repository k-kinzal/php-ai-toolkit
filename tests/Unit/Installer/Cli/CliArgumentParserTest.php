<?php

declare(strict_types=1);

namespace Tests\Unit\Installer\Cli;

use PhpAiToolkit\Installer\Cli\CliArgumentParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CliArgumentParser::class)]
final class CliArgumentParserTest extends TestCase
{
    public function testParseReturnsCommandAndFlags(): void
    {
        self::assertSame([
            'command' => 'install',
            'force' => true,
            'copy' => true,
            'help' => false,
            'version' => false,
        ], (new CliArgumentParser())->parse(['install', '--force', '--copy']));
    }

    public function testParseReturnsHelpAndVersionFlags(): void
    {
        self::assertSame([
            'command' => null,
            'force' => false,
            'copy' => false,
            'help' => true,
            'version' => true,
        ], (new CliArgumentParser())->parse(['-h', '-V']));
    }
}

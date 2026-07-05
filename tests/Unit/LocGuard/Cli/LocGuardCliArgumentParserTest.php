<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

use PhpAiToolkit\LocGuard\Cli\LocGuardCliArgumentParser;
use PhpAiToolkit\LocGuard\LocGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocGuardCliArgumentParser::class)]
final class LocGuardCliArgumentParserTest extends TestCase
{
    public function testParseReturnsConfigReporterAndFlags(): void
    {
        self::assertSame([
            'config' => 'custom.yaml',
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
}

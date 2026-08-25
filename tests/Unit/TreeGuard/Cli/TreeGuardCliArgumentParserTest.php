<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Cli\TreeGuardCliArgumentParser;
use Toolkit\TreeGuard\TreeGuardException;

/**
 * @covers \Toolkit\TreeGuard\Cli\TreeGuardCliArgumentParser
 * @uses \Toolkit\TreeGuard\TreeGuardException
 */
#[CoversClass(TreeGuardCliArgumentParser::class)]
#[UsesClass(TreeGuardException::class)]
final class TreeGuardCliArgumentParserTest extends TestCase
{
    public function testParseAppliesDefaults(): void
    {
        self::assertSame(
            ['config' => 'tree.yaml', 'help' => false, 'reporter' => null, 'version' => false],
            (new TreeGuardCliArgumentParser())->parse([]),
        );
    }

    public function testParseReadsHelpAndVersionFlags(): void
    {
        self::assertTrue((new TreeGuardCliArgumentParser())->parse(['--help'])['help']);
        self::assertTrue((new TreeGuardCliArgumentParser())->parse(['-h'])['help']);
        self::assertTrue((new TreeGuardCliArgumentParser())->parse(['--version'])['version']);
        self::assertTrue((new TreeGuardCliArgumentParser())->parse(['-V'])['version']);
    }

    public function testParseReadsConfigOptionForms(): void
    {
        self::assertSame('a.yaml', (new TreeGuardCliArgumentParser())->parse(['--config', 'a.yaml'])['config']);
        self::assertSame('b.yaml', (new TreeGuardCliArgumentParser())->parse(['--config=b.yaml'])['config']);
    }

    public function testParseReadsReporterOptionForms(): void
    {
        self::assertSame('json', (new TreeGuardCliArgumentParser())->parse(['--reporter', 'json'])['reporter']);
        self::assertSame('text', (new TreeGuardCliArgumentParser())->parse(['--reporter=text'])['reporter']);
        self::assertSame('ai', (new TreeGuardCliArgumentParser())->parse(['--format', 'ai'])['reporter']);
        self::assertSame('json', (new TreeGuardCliArgumentParser())->parse(['--format=json'])['reporter']);
    }

    public function testParseRejectsUnknownOption(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Unknown option: --unknown');

        (new TreeGuardCliArgumentParser())->parse(['--unknown']);
    }

    public function testParseRejectsMissingConfigValue(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Missing value for --config.');

        (new TreeGuardCliArgumentParser())->parse(['--config']);
    }

    public function testParseRejectsMissingReporterValueBeforeNextOption(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Missing value for --reporter.');

        (new TreeGuardCliArgumentParser())->parse(['--reporter', '--config']);
    }
}

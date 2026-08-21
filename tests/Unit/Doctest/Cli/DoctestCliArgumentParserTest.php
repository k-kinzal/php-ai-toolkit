<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Cli;

use PhpAiToolkit\Doctest\Cli\DoctestCliArgumentParser;
use PhpAiToolkit\Doctest\DoctestException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DoctestCliArgumentParser::class)]
final class DoctestCliArgumentParserTest extends TestCase
{
    public function testParseAppliesDefaultsWhenNothingIsGiven(): void
    {
        self::assertSame(
            ['config' => 'doctest.yaml', 'filter' => null, 'help' => false, 'list' => false, 'reporter' => null, 'version' => false],
            (new DoctestCliArgumentParser())->parse([]),
        );
    }

    public function testParseReadsSeparatedAndAttachedOptionValues(): void
    {
        $parser = new DoctestCliArgumentParser();
        $separated = $parser->parse(['--config', 'a.yaml', '--filter', 'Ledger', '--reporter', 'json']);
        $attached = $parser->parse(['--config=a.yaml', '--filter=Ledger', '--reporter=json']);

        self::assertSame($separated, $attached);
        self::assertSame('a.yaml', $separated['config']);
        self::assertSame('Ledger', $separated['filter']);
        self::assertSame('json', $separated['reporter']);
    }

    public function testParseAcceptsFormatAsAnAliasOfReporter(): void
    {
        $parser = new DoctestCliArgumentParser();

        self::assertSame('text', $parser->parse(['--format', 'text'])['reporter']);
        self::assertSame('text', $parser->parse(['--format=text'])['reporter']);
    }

    public function testParseReadsTheStandaloneFlags(): void
    {
        $parser = new DoctestCliArgumentParser();

        self::assertTrue($parser->parse(['--help'])['help']);
        self::assertTrue($parser->parse(['-h'])['help']);
        self::assertTrue($parser->parse(['--version'])['version']);
        self::assertTrue($parser->parse(['-V'])['version']);
        self::assertTrue($parser->parse(['--list'])['list']);
    }

    public function testParseRejectsAnUnknownOption(): void
    {
        $this->expectException(DoctestException::class);
        $this->expectExceptionMessage('Unknown option: --verbose');

        (new DoctestCliArgumentParser())->parse(['--verbose']);
    }

    public function testValueRejectsAnOptionWithoutAValue(): void
    {
        $parser = new DoctestCliArgumentParser();

        $this->expectException(DoctestException::class);
        $this->expectExceptionMessage('Missing value for --config.');

        $parser->value(['--config'], 0, '--config');
    }
}

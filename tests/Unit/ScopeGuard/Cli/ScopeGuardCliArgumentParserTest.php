<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Cli\ScopeGuardCliArgumentParser;
use Toolkit\ScopeGuard\ScopeGuardException;

/**
 * @covers \Toolkit\ScopeGuard\Cli\ScopeGuardCliArgumentParser
 * @uses \Toolkit\ScopeGuard\ScopeGuardException
 */
#[CoversClass(ScopeGuardCliArgumentParser::class)]
#[UsesClass(ScopeGuardException::class)]
final class ScopeGuardCliArgumentParserTest extends TestCase
{
    /**
     * @throws ScopeGuardException
     */
    public function testParseDefaultsToScopeYaml(): void
    {
        self::assertSame('scope.yaml', (new ScopeGuardCliArgumentParser())->parse([])['config']);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testParseReadsTheConfigOptionAsASeparateValue(): void
    {
        self::assertSame('other.yaml', (new ScopeGuardCliArgumentParser())->parse(['--config', 'other.yaml'])['config']);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testParseReadsTheConfigOptionWithAnEqualsSign(): void
    {
        self::assertSame('other.yaml', (new ScopeGuardCliArgumentParser())->parse(['--config=other.yaml'])['config']);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testParseReadsTheReporterOption(): void
    {
        self::assertSame('json', (new ScopeGuardCliArgumentParser())->parse(['--reporter', 'json'])['reporter']);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testParseReadsTheReporterOptionWithAnEqualsSign(): void
    {
        self::assertSame('json', (new ScopeGuardCliArgumentParser())->parse(['--reporter=json'])['reporter']);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testParseAcceptsFormatAsAnAliasOfReporter(): void
    {
        self::assertSame('text', (new ScopeGuardCliArgumentParser())->parse(['--format=text'])['reporter']);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testParseReadsTheHelpFlag(): void
    {
        self::assertTrue((new ScopeGuardCliArgumentParser())->parse(['-h'])['help']);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testParseReadsTheVersionFlag(): void
    {
        self::assertTrue((new ScopeGuardCliArgumentParser())->parse(['--version'])['version']);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testParseRejectsAConfigOptionWithoutValue(): void
    {
        $this->expectException(ScopeGuardException::class);

        (new ScopeGuardCliArgumentParser())->parse(['--config']);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testParseRejectsAReporterOptionWithoutValue(): void
    {
        $this->expectException(ScopeGuardException::class);

        (new ScopeGuardCliArgumentParser())->parse(['--reporter', '--help']);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testParseRejectsAnUnknownOption(): void
    {
        $this->expectException(ScopeGuardException::class);

        (new ScopeGuardCliArgumentParser())->parse(['--strict']);
    }
}

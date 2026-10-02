<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Cli\DocGuardCliArgumentParser;
use Toolkit\DocGuard\DocGuardException;

/**
 * @covers \Toolkit\DocGuard\Cli\DocGuardCliArgumentParser
 * @uses \Toolkit\DocGuard\DocGuardException
 */
#[CoversClass(DocGuardCliArgumentParser::class)]
#[UsesClass(DocGuardException::class)]
final class DocGuardCliArgumentParserTest extends TestCase
{
    public function testParseReturnsDefaults(): void
    {
        self::assertSame(
            ['config' => null, 'generate' => false, 'help' => false, 'paths' => [], 'reporter' => null, 'version' => false],
            (new DocGuardCliArgumentParser())->parse([]),
        );
    }

    public function testParseReadsInlineAndSeparateValues(): void
    {
        $arguments = (new DocGuardCliArgumentParser())->parse(['--config', 'docs.yaml', '--format=text', '-h', '-V']);

        self::assertSame('docs.yaml', $arguments['config']);
        self::assertSame('text', $arguments['reporter']);
        self::assertTrue($arguments['help']);
        self::assertTrue($arguments['version']);
        self::assertSame('json', (new DocGuardCliArgumentParser())->parse(['--reporter', 'json'])['reporter']);
        self::assertSame('ai', (new DocGuardCliArgumentParser())->parse(['--reporter=ai', '--config=a.yaml'])['reporter']);
    }

    public function testParseCollectsGeneratePaths(): void
    {
        $arguments = (new DocGuardCliArgumentParser())->parse(['--generate', 'README.md', 'docs']);

        self::assertTrue($arguments['generate']);
        self::assertSame(['README.md', 'docs'], $arguments['paths']);
    }

    public function testParseRejectsUnknownOption(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('Unknown option: --fix');

        (new DocGuardCliArgumentParser())->parse(['--fix']);
    }

    public function testParseRejectsMissingValue(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('Missing value for --config.');

        (new DocGuardCliArgumentParser())->parse(['--config', '--reporter=ai']);
    }

    public function testValidateAcceptsGeneratePathsAndCheckOptions(): void
    {
        $this->expectNotToPerformAssertions();

        (new DocGuardCliArgumentParser())->validate(['config' => null, 'generate' => true, 'paths' => ['docs'], 'reporter' => null]);
        (new DocGuardCliArgumentParser())->validate(['config' => 'doc-guard.yaml', 'generate' => false, 'paths' => [], 'reporter' => 'json']);
    }

    public function testValidateRejectsReporterWithGenerate(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('cannot be combined with --config, --reporter, or --format.');

        (new DocGuardCliArgumentParser())->validate(['config' => null, 'generate' => true, 'paths' => [], 'reporter' => 'text']);
    }

    public function testParseRejectsPathsWithoutGenerate(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('Unexpected argument: README.md. Paths are accepted only with --generate.');

        (new DocGuardCliArgumentParser())->parse(['README.md']);
    }

    public function testParseRejectsGenerateWithConfig(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('--generate prints a new config to standard output and cannot be combined with --config, --reporter, or --format.');

        (new DocGuardCliArgumentParser())->parse(['--generate', '--config=doc-guard.yaml']);
    }
}

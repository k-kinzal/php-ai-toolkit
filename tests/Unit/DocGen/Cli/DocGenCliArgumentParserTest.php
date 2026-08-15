<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PhpAiToolkit\DocGen\Cli\DocGenCliArgumentParser;
use PhpAiToolkit\DocGen\DocGenException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocGenCliArgumentParser::class)]
#[UsesClass(DocGenException::class)]
final class DocGenCliArgumentParserTest extends TestCase
{
    public function testParseReturnsInactiveDefaults(): void
    {
        self::assertSame(
            ['config' => null, 'output' => null, 'vendor' => null, 'vendorDev' => null, 'coverage' => null, 'serve' => null, 'memoryLimit' => null, 'help' => false, 'version' => false],
            (new DocGenCliArgumentParser())->parse([]),
        );
    }

    public function testParseRecognizesHelpOptions(): void
    {
        self::assertTrue((new DocGenCliArgumentParser())->parse(['--help'])['help']);
        self::assertTrue((new DocGenCliArgumentParser())->parse(['-h'])['help']);
    }

    public function testParseRecognizesVersionOptions(): void
    {
        self::assertTrue((new DocGenCliArgumentParser())->parse(['--version'])['version']);
        self::assertTrue((new DocGenCliArgumentParser())->parse(['-V'])['version']);
    }

    public function testParseReadsMemoryLimitValue(): void
    {
        self::assertSame('1G', (new DocGenCliArgumentParser())->parse(['--memory-limit=1G'])['memoryLimit']);
        self::assertSame('-1', (new DocGenCliArgumentParser())->parse(['--memory-limit=-1'])['memoryLimit']);
        self::assertSame('512M', (new DocGenCliArgumentParser())->parse(['--memory-limit', '512M'])['memoryLimit']);
    }

    public function testParseRejectsMalformedMemoryLimit(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid --memory-limit value: plenty. Use a byte count, a value such as 512M or 1G, or -1 for no limit.');

        (new DocGenCliArgumentParser())->parse(['--memory-limit=plenty']);
    }

    public function testMemoryLimitAcceptsSupportedValues(): void
    {
        self::assertSame('134217728', (new DocGenCliArgumentParser())->memoryLimit('134217728'));
        self::assertSame('256K', (new DocGenCliArgumentParser())->memoryLimit('256K'));
        self::assertSame('-1', (new DocGenCliArgumentParser())->memoryLimit('-1'));
    }

    public function testMemoryLimitRejectsUnsupportedValue(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid --memory-limit value: 12MB.');

        (new DocGenCliArgumentParser())->memoryLimit('12MB');
    }

    public function testParseUsesDefaultServeAddress(): void
    {
        self::assertSame('127.0.0.1:8090', (new DocGenCliArgumentParser())->parse(['--serve'])['serve']);
    }

    public function testParseExpandsBarePortServeValue(): void
    {
        self::assertSame('127.0.0.1:9000', (new DocGenCliArgumentParser())->parse(['--serve=9000'])['serve']);
    }

    public function testParseKeepsHostPortServeValue(): void
    {
        self::assertSame('0.0.0.0:8080', (new DocGenCliArgumentParser())->parse(['--serve=0.0.0.0:8080'])['serve']);
    }

    public function testParseRejectsMalformedServeValue(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid --serve address: abc. Use HOST:PORT or a port number.');

        (new DocGenCliArgumentParser())->parse(['--serve=abc']);
    }

    public function testParseTreatsBareVendorAsMatchAll(): void
    {
        self::assertSame(['*'], (new DocGenCliArgumentParser())->parse(['--vendor'])['vendor']);
    }

    public function testParseSplitsVendorValueIntoGlobs(): void
    {
        self::assertSame(['a/*', 'b/*'], (new DocGenCliArgumentParser())->parse(['--vendor=a/*,b/*'])['vendor']);
    }

    public function testParseRejectsEmptyVendorValue(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Option --vendor requires at least one package name glob.');

        (new DocGenCliArgumentParser())->parse(['--vendor=']);
    }

    public function testParseTreatsBareVendorDevAsMatchAll(): void
    {
        $options = (new DocGenCliArgumentParser())->parse(['--vendor-dev']);

        self::assertSame(['*'], $options['vendorDev']);
        self::assertNull($options['vendor']);
    }

    public function testParseSplitsVendorDevValueIntoGlobs(): void
    {
        self::assertSame(['phpunit/*', 'phpstan/phpstan'], (new DocGenCliArgumentParser())->parse(['--vendor-dev=phpunit/*,phpstan/phpstan'])['vendorDev']);
    }

    public function testParseKeepsVendorAndVendorDevGlobsApart(): void
    {
        $options = (new DocGenCliArgumentParser())->parse(['--vendor=acme/*', '--vendor-dev']);

        self::assertSame(['acme/*'], $options['vendor']);
        self::assertSame(['*'], $options['vendorDev']);
    }

    public function testParseRejectsEmptyVendorDevValue(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Option --vendor-dev requires at least one package name glob.');

        (new DocGenCliArgumentParser())->parse(['--vendor-dev=']);
    }

    public function testVendorGlobsExpandsBareOptionToMatchAll(): void
    {
        self::assertSame(['*'], (new DocGenCliArgumentParser())->vendorGlobs('--vendor-dev', '--vendor-dev'));
    }

    public function testVendorGlobsSplitsInlineValue(): void
    {
        self::assertSame(['acme/*', 'other/lib'], (new DocGenCliArgumentParser())->vendorGlobs('--vendor=acme/*,other/lib', '--vendor'));
    }

    public function testParseReadsInlineOptionValues(): void
    {
        $options = (new DocGenCliArgumentParser())->parse(['--config=doc.yaml', '--output=public/docs', '--coverage=build/coverage-xml']);

        self::assertSame('doc.yaml', $options['config']);
        self::assertSame('public/docs', $options['output']);
        self::assertSame('build/coverage-xml', $options['coverage']);
    }

    public function testParseReadsSeparateOptionValues(): void
    {
        $options = (new DocGenCliArgumentParser())->parse(['--config', 'other.yaml', '--output', 'site', '--coverage', 'cov', '--help']);

        self::assertSame('other.yaml', $options['config']);
        self::assertSame('site', $options['output']);
        self::assertSame('cov', $options['coverage']);
        self::assertTrue($options['help']);
    }

    public function testParseRejectsMissingOptionValue(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Option --config requires a value.');

        (new DocGenCliArgumentParser())->parse(['--config']);
    }

    public function testParseRejectsUnknownOption(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Unknown option: --bogus');

        (new DocGenCliArgumentParser())->parse(['--bogus']);
    }

    public function testValueOptionReturnsInlineValue(): void
    {
        self::assertSame('doc.yaml', (new DocGenCliArgumentParser())->valueOption('--config=doc.yaml', 'config'));
    }

    public function testValueOptionReturnsNullForEmptyOrForeignArgument(): void
    {
        self::assertNull((new DocGenCliArgumentParser())->valueOption('--config=', 'config'));
        self::assertNull((new DocGenCliArgumentParser())->valueOption('--output=site', 'config'));
        self::assertNull((new DocGenCliArgumentParser())->valueOption('--config', 'config'));
    }

    public function testTakeReturnsInlineValue(): void
    {
        self::assertSame('doc.yaml', (new DocGenCliArgumentParser())->take(['--config=doc.yaml'], 0, 'config'));
    }

    public function testTakeReturnsFollowingArgument(): void
    {
        self::assertSame('doc.yaml', (new DocGenCliArgumentParser())->take(['--config', 'doc.yaml'], 0, 'config'));
    }

    public function testTakeRejectsMissingValue(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Option --config requires a value.');

        (new DocGenCliArgumentParser())->take(['--config'], 0, 'config');
    }

    public function testTakeRejectsOptionLikeValue(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Option --output requires a value.');

        (new DocGenCliArgumentParser())->take(['--output', '--serve'], 0, 'output');
    }

    public function testConsumedDistinguishesInlineAndSeparateValues(): void
    {
        self::assertSame(0, (new DocGenCliArgumentParser())->consumed('--config=doc.yaml'));
        self::assertSame(1, (new DocGenCliArgumentParser())->consumed('--config'));
    }

    public function testGlobListTrimsAndDropsEmptySegments(): void
    {
        self::assertSame(['a/*', 'b'], (new DocGenCliArgumentParser())->globList(' a/* , b ,', '--vendor'));
    }

    public function testGlobListRejectsValueWithoutGlobs(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Option --vendor-dev requires at least one package name glob.');

        (new DocGenCliArgumentParser())->globList(' , ', '--vendor-dev');
    }

    public function testAddressExpandsBarePort(): void
    {
        self::assertSame('127.0.0.1:8090', (new DocGenCliArgumentParser())->address('8090'));
    }

    public function testAddressKeepsHostPortValue(): void
    {
        self::assertSame('docs.local:80', (new DocGenCliArgumentParser())->address('docs.local:80'));
        self::assertSame('0.0.0.0:8080', (new DocGenCliArgumentParser())->address('0.0.0.0:8080'));
    }

    public function testAddressRejectsMalformedValue(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid --serve address: not valid. Use HOST:PORT or a port number.');

        (new DocGenCliArgumentParser())->address('not valid');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Cli\DocGenCliArgumentParser;
use Toolkit\DocGen\Config\BaseUrl;
use Toolkit\DocGen\Config\RepositoryUrl;
use Toolkit\DocGen\DocGenException;

/**
 * @covers \Toolkit\DocGen\Cli\DocGenCliArgumentParser
 * @uses \Toolkit\DocGen\Config\BaseUrl
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Config\RepositoryUrl
 */
#[CoversClass(DocGenCliArgumentParser::class)]
#[UsesClass(BaseUrl::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(RepositoryUrl::class)]
final class DocGenCliArgumentParserTest extends TestCase
{
    public function testParseReturnsInactiveDefaults(): void
    {
        self::assertSame(
            ['packages' => null, 'vendor' => null, 'vendorDev' => null, 'exclude' => null, 'output' => null, 'title' => null, 'deptrac' => null, 'coverage' => null, 'cacheDir' => null, 'baseUrl' => null, 'repository' => null, 'serve' => null, 'memoryLimit' => null, 'jobs' => null, 'base' => null, 'head' => null, 'publicApi' => false, 'noCache' => false, 'clearCache' => false, 'help' => false, 'version' => false],
            (new DocGenCliArgumentParser())->parse([]),
        );
    }

    public function testParseEnablesPublicApiModeExplicitly(): void
    {
        self::assertTrue((new DocGenCliArgumentParser())->parse(['--public-api'])['publicApi']);
        self::assertFalse((new DocGenCliArgumentParser())->parse([])['publicApi']);
    }

    public function testParseReadsDiffRangeAsBaseAndHead(): void
    {
        $options = (new DocGenCliArgumentParser())->parse(['--diff=main..HEAD']);

        self::assertSame('main', $options['base']);
        self::assertSame('HEAD', $options['head']);
    }

    public function testParseReadsDiffWithoutHeadAsWorkingTreeComparison(): void
    {
        $options = (new DocGenCliArgumentParser())->parse(['--diff', 'v1.0.0']);

        self::assertSame('v1.0.0', $options['base']);
        self::assertNull($options['head']);
    }

    public function testParseReadsSeparateBaseAndHeadOptions(): void
    {
        $options = (new DocGenCliArgumentParser())->parse(['--base=main', '--head', 'feature']);

        self::assertSame('main', $options['base']);
        self::assertSame('feature', $options['head']);
    }

    public function testParseRejectsHeadWithoutBase(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Option --head needs a revision to compare against: add --base=REVISION, or use --diff=BASE..HEAD.');

        (new DocGenCliArgumentParser())->parse(['--head=HEAD']);
    }

    public function testParseRejectsDiffRangeWithoutBaseRevision(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid --diff range: ..HEAD. Use BASE to compare against the working tree, or BASE..HEAD to compare two revisions.');

        (new DocGenCliArgumentParser())->parse(['--diff=..HEAD']);
    }

    public function testIsValueOptionRecognizesEveryOptionThatCarriesAValue(): void
    {
        $parser = new DocGenCliArgumentParser();

        self::assertTrue($parser->isValueOption('--diff'));
        self::assertTrue($parser->isValueOption('--base=main'));
        self::assertTrue($parser->isValueOption('--head=HEAD'));
        self::assertTrue($parser->isValueOption('--output=build'));
        self::assertTrue($parser->isValueOption('--packages=.'));
        self::assertTrue($parser->isValueOption('--exclude=tests/Fixture/*'));
        self::assertTrue($parser->isValueOption('--title=Docs'));
        self::assertTrue($parser->isValueOption('--deptrac=deptrac.yaml'));
        self::assertTrue($parser->isValueOption('--repository'));
        self::assertTrue($parser->isValueOption('--memory-limit'));
        self::assertFalse($parser->isValueOption('--serve'));
        self::assertFalse($parser->isValueOption('--diffuse'));
    }

    public function testOptionNameStripsThePrefixAndTheInlineValue(): void
    {
        $parser = new DocGenCliArgumentParser();

        self::assertSame('diff', $parser->optionName('--diff=main..HEAD'));
        self::assertSame('base', $parser->optionName('--base'));
    }

    public function testApplyValueOptionAssignsEveryOptionThatDescribesTheSite(): void
    {
        $parser = new DocGenCliArgumentParser();
        $defaults = $parser->parse([]);

        self::assertSame(['.', 'packages/*'], $parser->applyValueOption($defaults, 'packages', '.,packages/*')['packages']);
        self::assertSame(['tests/Fixture/*'], $parser->applyValueOption($defaults, 'exclude', 'tests/Fixture/*')['exclude']);
        self::assertSame('build/site', $parser->applyValueOption($defaults, 'output', 'build/site')['output']);
        self::assertSame('My Project', $parser->applyValueOption($defaults, 'title', 'My Project')['title']);
        self::assertSame('conf/deptrac.yaml', $parser->applyValueOption($defaults, 'deptrac', 'conf/deptrac.yaml')['deptrac']);
        self::assertSame('build/cov', $parser->applyValueOption($defaults, 'coverage', 'build/cov')['coverage']);
        self::assertSame('https://example.github.io/project', $parser->applyValueOption($defaults, 'base-url', 'https://example.github.io/project/')['baseUrl']);
        self::assertSame('https://github.com/example/project', $parser->applyValueOption($defaults, 'repository', 'https://github.com/example/project/')['repository']);
    }

    public function testApplyValueOptionHandsTheRunOptionsOn(): void
    {
        $parser = new DocGenCliArgumentParser();
        $defaults = $parser->parse([]);

        self::assertSame('1G', $parser->applyValueOption($defaults, 'memory-limit', '1G')['memoryLimit']);
        self::assertSame('v2', $parser->applyValueOption($defaults, 'diff', 'v1..v2')['head']);
    }

    public function testApplyRunOptionAssignsEveryOptionThatDecidesHowARunIsCarriedOut(): void
    {
        $parser = new DocGenCliArgumentParser();
        $defaults = $parser->parse([]);

        self::assertSame('1G', $parser->applyRunOption($defaults, 'memory-limit', '1G')['memoryLimit']);
        self::assertSame(4, $parser->applyRunOption($defaults, 'jobs', '4')['jobs']);
        self::assertSame('.docgen', $parser->applyRunOption($defaults, 'cache-dir', '.docgen')['cacheDir']);
        self::assertSame('main', $parser->applyRunOption($defaults, 'base', 'main')['base']);
        self::assertSame('HEAD', $parser->applyRunOption($defaults, 'head', 'HEAD')['head']);
        self::assertSame('v2', $parser->applyRunOption($defaults, 'diff', 'v1..v2')['head']);
    }

    public function testRevisionRangeKeepsAnEarlierHeadWhenTheRangeOmitsOne(): void
    {
        $parser = new DocGenCliArgumentParser();
        $options = $parser->parse(['--head=feature', '--base=main']);

        self::assertSame('feature', $parser->revisionRange($options, 'v1.0.0')['head']);
        self::assertSame('v1.0.0', $parser->revisionRange($options, 'v1.0.0')['base']);
    }

    public function testValidatedAcceptsABaseWithoutAHead(): void
    {
        $parser = new DocGenCliArgumentParser();

        self::assertSame('main', $parser->validated($parser->parse(['--base=main']))['base']);
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

    public function testJobsAcceptsAWorkerCountOfOneOrMore(): void
    {
        self::assertSame(1, (new DocGenCliArgumentParser())->jobs('1'));
        self::assertSame(12, (new DocGenCliArgumentParser())->jobs('12'));
    }

    public function testJobsRejectsAnythingThatIsNotAWorkerCount(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid --jobs value: 0.');

        (new DocGenCliArgumentParser())->jobs('0');
    }

    public function testParseReadsTheWorkerCountAndLeavesItUnsetByDefault(): void
    {
        self::assertSame(4, (new DocGenCliArgumentParser())->parse(['--jobs=4'])['jobs']);
        self::assertSame(2, (new DocGenCliArgumentParser())->parse(['--jobs', '2'])['jobs']);
        self::assertNull((new DocGenCliArgumentParser())->parse([])['jobs']);
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
        $options = (new DocGenCliArgumentParser())->parse(['--title=Docs', '--output=public/docs', '--coverage=build/coverage-xml']);

        self::assertSame('Docs', $options['title']);
        self::assertSame('public/docs', $options['output']);
        self::assertSame('build/coverage-xml', $options['coverage']);
    }

    public function testParseReadsSeparateOptionValues(): void
    {
        $options = (new DocGenCliArgumentParser())->parse(['--deptrac', 'conf/deptrac.yaml', '--output', 'site', '--coverage', 'cov', '--help']);

        self::assertSame('conf/deptrac.yaml', $options['deptrac']);
        self::assertSame('site', $options['output']);
        self::assertSame('cov', $options['coverage']);
        self::assertTrue($options['help']);
    }

    public function testParseReadsThePackageAndExcludeGlobs(): void
    {
        $options = (new DocGenCliArgumentParser())->parse(['--packages=.,packages/*', '--exclude=tests/Fixture/*']);

        self::assertSame(['.', 'packages/*'], $options['packages']);
        self::assertSame(['tests/Fixture/*'], $options['exclude']);
    }

    public function testParseAddsUpEveryOccurrenceOfAListOption(): void
    {
        $options = (new DocGenCliArgumentParser())->parse(['--exclude=build/*', '--exclude=tests/Fixture/*', '--vendor=acme/*', '--vendor=other/*']);

        self::assertSame(['build/*', 'tests/Fixture/*'], $options['exclude']);
        self::assertSame(['acme/*', 'other/*'], $options['vendor']);
    }

    public function testParseRejectsAPackagesValueWithoutGlobs(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Option --packages requires at least one directory glob.');

        (new DocGenCliArgumentParser())->parse(['--packages= , ']);
    }

    public function testParseRejectsAnExcludeValueWithoutGlobs(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Option --exclude requires at least one path glob.');

        (new DocGenCliArgumentParser())->parse(['--exclude= , ']);
    }

    public function testParseNormalizesTheSiteAndRepositoryAddresses(): void
    {
        $options = (new DocGenCliArgumentParser())->parse(['--base-url=https://example.github.io/project/', '--repository=https://github.com/example/project/']);

        self::assertSame('https://example.github.io/project', $options['baseUrl']);
        self::assertSame('https://github.com/example/project', $options['repository']);
    }

    public function testParseRejectsARepositoryThatIsNotAnAbsoluteHttpAddress(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid --repository value: git@github.com:example/project.git. Use the absolute address of the repository the project lives in, such as https://github.com/example/project.');

        (new DocGenCliArgumentParser())->parse(['--repository=git@github.com:example/project.git']);
    }

    public function testParseRejectsMissingOptionValue(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Option --output requires a value.');

        (new DocGenCliArgumentParser())->parse(['--output']);
    }

    public function testParseRejectsUnknownOption(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Unknown option: --bogus');

        (new DocGenCliArgumentParser())->parse(['--bogus']);
    }

    public function testValueOptionReturnsInlineValue(): void
    {
        self::assertSame('build/site', (new DocGenCliArgumentParser())->valueOption('--output=build/site', 'output'));
    }

    public function testValueOptionReturnsNullForEmptyOrForeignArgument(): void
    {
        self::assertNull((new DocGenCliArgumentParser())->valueOption('--output=', 'output'));
        self::assertNull((new DocGenCliArgumentParser())->valueOption('--coverage=cov', 'output'));
        self::assertNull((new DocGenCliArgumentParser())->valueOption('--output', 'output'));
    }

    public function testTakeReturnsInlineValue(): void
    {
        self::assertSame('build/site', (new DocGenCliArgumentParser())->take(['--output=build/site'], 0, 'output'));
    }

    public function testTakeReturnsFollowingArgument(): void
    {
        self::assertSame('build/site', (new DocGenCliArgumentParser())->take(['--output', 'build/site'], 0, 'output'));
    }

    public function testTakeRejectsMissingValue(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Option --coverage requires a value.');

        (new DocGenCliArgumentParser())->take(['--coverage'], 0, 'coverage');
    }

    public function testTakeRejectsOptionLikeValue(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Option --output requires a value.');

        (new DocGenCliArgumentParser())->take(['--output', '--serve'], 0, 'output');
    }

    public function testConsumedDistinguishesInlineAndSeparateValues(): void
    {
        self::assertSame(0, (new DocGenCliArgumentParser())->consumed('--output=build/site'));
        self::assertSame(1, (new DocGenCliArgumentParser())->consumed('--output'));
    }

    public function testGlobListTrimsAndDropsEmptySegments(): void
    {
        self::assertSame(['a/*', 'b'], (new DocGenCliArgumentParser())->globList(' a/* , b ,', '--vendor', 'package name glob'));
    }

    public function testGlobListRejectsValueWithoutGlobs(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Option --vendor-dev requires at least one package name glob.');

        (new DocGenCliArgumentParser())->globList(' , ', '--vendor-dev', 'package name glob');
    }

    public function testGlobListNamesWhatTheRejectedOptionExpects(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Option --exclude requires at least one path glob.');

        (new DocGenCliArgumentParser())->globList('', '--exclude', 'path glob');
    }

    public function testAppendGlobsAddsToWhatTheEarlierOccurrencesNamed(): void
    {
        $parser = new DocGenCliArgumentParser();

        self::assertSame(['a/*'], $parser->appendGlobs(null, ['a/*']));
        self::assertSame(['a/*', 'b/*'], $parser->appendGlobs(['a/*'], ['b/*']));
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

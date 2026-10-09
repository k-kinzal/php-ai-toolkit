<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Toolkit\DocGen\Cli\DocGenCliArgumentParser;
use Toolkit\DocGen\Model\Config\BaseUrl;
use Toolkit\DocGen\Model\Config\RepositoryUrl;
use Toolkit\DocGen\Model\DocGenException;

/**
 * @covers \Toolkit\DocGen\Cli\DocGenCliArgumentParser
 * @uses \Toolkit\DocGen\Model\Config\BaseUrl
 * @uses \Toolkit\DocGen\Model\DocGenException
 * @uses \Toolkit\DocGen\Model\Config\RepositoryUrl
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
            ['packages' => ['.', 'packages/*'], 'vendor' => null, 'vendorDev' => null, 'exclude' => null, 'output' => 'build/docs', 'title' => null, 'deptrac' => null, 'coverage' => null, 'cacheDir' => 'build/docgen-cache', 'baseUrl' => null, 'repository' => null, 'serve' => null, 'memoryLimit' => null, 'jobs' => null, 'base' => null, 'head' => null, 'publicApi' => false, 'noCache' => false, 'clearCache' => false],
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

    public function testParseReadsInlineOptionValues(): void
    {
        $options = (new DocGenCliArgumentParser())->parse(['--title=Docs', '--output=public/docs', '--coverage=build/coverage-xml']);

        self::assertSame('Docs', $options['title']);
        self::assertSame('public/docs', $options['output']);
        self::assertSame('build/coverage-xml', $options['coverage']);
    }

    public function testParseReadsSeparateOptionValues(): void
    {
        $options = (new DocGenCliArgumentParser())->parse(['--deptrac', 'conf/deptrac.yaml', '--output', 'site', '--coverage', 'cov']);

        self::assertSame('conf/deptrac.yaml', $options['deptrac']);
        self::assertSame('site', $options['output']);
        self::assertSame('cov', $options['coverage']);
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
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The "--output" option requires a value.');

        (new DocGenCliArgumentParser())->parse(['--output']);
    }

    public function testParseRejectsEmptyOptionValue(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Option --output requires a value, such as --output=VALUE.');

        (new DocGenCliArgumentParser())->parse(['--output=']);
    }

    public function testParseRejectsUnknownOption(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The "--bogus" option does not exist.');

        (new DocGenCliArgumentParser())->parse(['--bogus']);
    }

    public function testParseRejectsArguments(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No arguments expected, got "src".');

        (new DocGenCliArgumentParser())->parse(['src']);
    }

    public function testParseReadsTheShortOptions(): void
    {
        $options = (new DocGenCliArgumentParser())->parse(['-o', 'public/docs', '-j2']);

        self::assertSame('public/docs', $options['output']);
        self::assertSame(2, $options['jobs']);
    }

    public function testParseLetsBaseAndHeadOverrideTheSidesOfTheDiffRange(): void
    {
        $parser = new DocGenCliArgumentParser();

        self::assertSame(['v1', 'feature'], array_values(array_intersect_key($parser->parse(['--diff=v1', '--head=feature']), ['base' => 0, 'head' => 0])));
        self::assertSame(['main', 'v2'], array_values(array_intersect_key($parser->parse(['--base=main', '--diff=v1..v2']), ['base' => 0, 'head' => 0])));
    }

    public function testParseAddsABareVendorToTheGlobsOfTheOtherOccurrences(): void
    {
        self::assertSame(['acme/*', '*'], (new DocGenCliArgumentParser())->parse(['--vendor=acme/*', '--vendor'])['vendor']);
    }

    public function testParseReadsAServeAddressGivenAsTheNextArgument(): void
    {
        $parser = new DocGenCliArgumentParser();

        self::assertSame('127.0.0.1:9001', $parser->parse(['--serve', '9001'])['serve']);
        self::assertSame('127.0.0.1:8090', $parser->parse(['--serve', '--no-cache'])['serve']);
        self::assertNull($parser->parse([])['serve']);
    }

    public function testDefinitionDeclaresEveryOptionOfTheCommand(): void
    {
        $definition = (new DocGenCliArgumentParser())->definition();

        self::assertSame(
            ['packages', 'exclude', 'vendor', 'vendor-dev', 'output', 'title', 'public-api', 'deptrac', 'coverage', 'base-url', 'repository', 'diff', 'base', 'head', 'cache-dir', 'no-cache', 'clear-cache', 'serve', 'memory-limit', 'jobs'],
            array_keys($definition->getOptions()),
        );
        self::assertSame('output', $definition->getOptionForShortcut('o')->getName());
        self::assertSame('jobs', $definition->getOptionForShortcut('j')->getName());
        self::assertSame([], $definition->getArguments());
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

    public function testReadReadsInputBoundToTheDefinition(): void
    {
        $parser = new DocGenCliArgumentParser();
        $options = $parser->read(new ArrayInput(['--title' => 'Docs', '-j' => '3', '--no-cache' => true], $parser->definition()));

        self::assertSame('Docs', $options['title']);
        self::assertSame(3, $options['jobs']);
        self::assertTrue($options['noCache']);
        self::assertFalse($options['clearCache']);
    }

    public function testTextReturnsTheValueOrNullWhenTheOptionIsAbsent(): void
    {
        $parser = new DocGenCliArgumentParser();
        $input = new ArgvInput(['docgen', '--title=Docs'], $parser->definition());

        self::assertSame('Docs', $parser->text($input, 'title'));
        self::assertNull($parser->text($input, 'deptrac'));
    }

    public function testTextRejectsABlankValue(): void
    {
        $parser = new DocGenCliArgumentParser();

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Option --title requires a value, such as --title=VALUE.');

        $parser->text(new ArgvInput(['docgen', '--title= '], $parser->definition()), 'title');
    }

    public function testGlobsAddsUpEveryOccurrenceOrIsNullWithoutOne(): void
    {
        $parser = new DocGenCliArgumentParser();
        $input = new ArgvInput(['docgen', '--exclude=a/*,b/*', '--exclude', 'c/*'], $parser->definition());

        self::assertSame(['a/*', 'b/*', 'c/*'], $parser->globs($input, 'exclude', 'path glob'));
        self::assertNull($parser->globs(new ArgvInput(['docgen'], $parser->definition()), 'exclude', 'path glob'));
    }

    public function testVendorGlobsExpandsABareOccurrenceToMatchAll(): void
    {
        $parser = new DocGenCliArgumentParser();
        $input = new ArgvInput(['docgen', '--vendor-dev', '--vendor-dev=phpunit/*'], $parser->definition());

        self::assertSame(['*', 'phpunit/*'], $parser->vendorGlobs($input, 'vendor-dev'));
        self::assertNull($parser->vendorGlobs($input, 'vendor'));
    }

    public function testValuesListsABareOccurrenceAsNull(): void
    {
        $parser = new DocGenCliArgumentParser();
        $input = new ArgvInput(['docgen', '--vendor', '--vendor=acme/*'], $parser->definition());

        self::assertSame([null, 'acme/*'], $parser->values($input, 'vendor'));
        self::assertSame([], $parser->values($input, 'vendor-dev'));
    }

    public function testServeIsNullUnlessTheSiteIsServed(): void
    {
        $parser = new DocGenCliArgumentParser();

        self::assertNull($parser->serve(new ArgvInput(['docgen'], $parser->definition())));
        self::assertSame('127.0.0.1:8090', $parser->serve(new ArgvInput(['docgen', '--serve'], $parser->definition())));
        self::assertSame('localhost:8000', $parser->serve(new ArgvInput(['docgen', '--serve=localhost:8000'], $parser->definition())));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Coverage;

use DOMDocument;
use DOMElement;
use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageIndex;
use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageReader;
use PhpAiToolkit\DocGen\Analysis\Coverage\MethodCoverage;
use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;

#[CoversClass(CoverageReader::class)]
#[UsesClass(CoverageIndex::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(DocGenPathResolver::class)]
#[UsesClass(MethodCoverage::class)]
final class CoverageReaderTest extends TestCase
{
    public function testReadRebasesReportPathsUsingTheIndexSource(): void
    {
        $root = sys_get_temp_dir() . '/docgen-coverage-' . uniqid('', true);
        $dir = $root . '/coverage-xml';
        mkdir($dir . '/Sub', 0777, true);
        file_put_contents($dir . '/index.xml', '<?xml version="1.0"?><phpunit><project source="' . $root . '/src"><tests/><file name="Listed.php"><coverage><line nr="3"><covered by="Tests\IndexTest::testListed"/></line></coverage></file></project></phpunit>');
        file_put_contents($dir . '/Sub/Foo.php.xml', <<<'XML'
<?xml version="1.0"?><phpunit><file name="Foo.php" path="/Sub"><class name="Foo"><method name="bar" start="10" end="20" crap="1" executable="5" executed="5" coverage="100"/></class><coverage><line nr="12"><covered by="Tests\FooTest::testBar"/><covered by="Tests\FooTest::testBar with data set #0"/></line></coverage></file></phpunit>
XML);

        $index = (new CoverageReader())->read($dir, $root);

        self::assertSame(['Tests\FooTest::testBar'], $index->testsForRange('src/Sub/Foo.php', 10, 20));
        self::assertSame(100.0, $index->methodAt('src/Sub/Foo.php', 10, 20)?->percent);
        self::assertSame(5, $index->methodAt('src/Sub/Foo.php', 10, 20)->executable);
        self::assertSame(5, $index->methodAt('src/Sub/Foo.php', 10, 20)->executed);
        self::assertSame([], $index->testsForRange('src/Listed.php', 1, 100));
    }

    public function testReadFallsBackToFullFileNamesWithoutAnIndex(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-coverage-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/Foo.php.xml', <<<'XML'
<?xml version="1.0"?><phpunit><file name="/src/Foo.php"><class name="Foo"><method name="bar" start="10" end="20" crap="1" executable="5" executed="5" coverage="100"/></class><coverage><line nr="12"><covered by="Tests\FooTest::testBar"/><covered by="Tests\FooTest::testBar with data set #0"/></line></coverage></file></phpunit>
XML);

        $index = (new CoverageReader())->read($dir, $dir);

        self::assertSame(['Tests\FooTest::testBar'], $index->testsForRange('src/Foo.php', 10, 20));
        self::assertSame(100.0, $index->methodAt('src/Foo.php', 10, 20)?->percent);
    }

    public function testReadRejectsMissingDirectory(): void
    {
        $missing = sys_get_temp_dir() . '/docgen-coverage-' . uniqid('', true) . '/absent';

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Coverage report directory not found: ' . $missing);

        (new CoverageReader())->read($missing, sys_get_temp_dir());
    }

    public function testSourcePrefixReturnsTheProjectRelativeSourceDirectory(): void
    {
        $root = sys_get_temp_dir() . '/docgen-coverage-' . uniqid('', true);
        $dir = $root . '/coverage-xml';
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/index.xml', '<?xml version="1.0"?><phpunit><project source="' . $root . '/packages/app/src/"><tests/></project></phpunit>');

        self::assertSame('packages/app/src', (new CoverageReader())->sourcePrefix($dir, $root));
    }

    #[WithoutErrorHandler]
    public function testSourcePrefixReturnsEmptyStringWithoutUsableIndex(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-coverage-' . uniqid('', true);
        mkdir($dir, 0777, true);
        $reader = new CoverageReader();

        self::assertSame('', $reader->sourcePrefix($dir, $dir));

        file_put_contents($dir . '/index.xml', 'not xml at all');

        self::assertSame('', $reader->sourcePrefix($dir, $dir));

        file_put_contents($dir . '/index.xml', '<?xml version="1.0"?><phpunit><project source="/elsewhere/src"><tests/></project></phpunit>');

        self::assertSame('', $reader->sourcePrefix($dir, $dir));
    }

    public function testReadReportFileReadsBothFileNameFormats(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-coverage-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/report.xml', <<<'XML'
<?xml version="1.0"?><phpunit><file><coverage><line nr="2"><covered by="Tests\UnnamedTest::testSkipped"/></line></coverage></file><file name="/src/Old.php"><coverage><line nr="3"><covered by="Tests\OldTest::testA"/></line></coverage></file><file name="New.php" path="/Sub"><coverage><line nr="4"><covered by="Tests\NewTest::testB"/></line></coverage></file></phpunit>
XML);

        $index = new CoverageIndex();
        (new CoverageReader())->readReportFile($dir . '/report.xml', '', $index);

        self::assertSame(['Tests\OldTest::testA'], $index->testsForRange('src/Old.php', 3, 3));
        self::assertSame(['Tests\NewTest::testB'], $index->testsForRange('Sub/New.php', 4, 4));

        $prefixed = new CoverageIndex();
        (new CoverageReader())->readReportFile($dir . '/report.xml', 'app', $prefixed);

        self::assertSame(['Tests\NewTest::testB'], $prefixed->testsForRange('app/Sub/New.php', 4, 4));
    }

    #[WithoutErrorHandler]
    public function testReadReportFileIgnoresUnloadableXmlAndUnnamedFiles(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-coverage-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/broken.xml', 'not xml');
        file_put_contents($dir . '/unnamed.xml', '<?xml version="1.0"?><phpunit><file><coverage><line nr="1"><covered by="Tests\T::testT"/></line></coverage></file></phpunit>');

        $index = new CoverageIndex();
        (new CoverageReader())->readReportFile($dir . '/broken.xml', '', $index);
        (new CoverageReader())->readReportFile($dir . '/unnamed.xml', '', $index);

        self::assertTrue($index->isEmpty());
    }

    public function testReadLinesSkipsInvalidLinesAndNormalizesTestIds(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<file name="Foo.php"><coverage><line nr="3"><covered by="Tests\ATest::testX with data set #1"/><covered by="Tests\ATest::testX"/></line><line nr="0"><covered by="Tests\ATest::testY"/></line><line nr="7"/></coverage></file>');
        $file = $document->getElementsByTagName('file')->item(0);
        assert($file instanceof DOMElement);

        $index = new CoverageIndex();
        (new CoverageReader())->readLines($file, 'src/A.php', $index);

        self::assertSame(['Tests\ATest::testX'], $index->testsForRange('src/A.php', 0, 100));
    }

    public function testReadMethodsSkipsMethodsWithoutAStartLine(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<file name="Foo.php"><class name="C"><method name="a" start="0" end="4" executable="1" executed="0" coverage="0"/><method name="b" start="7" end="9" executable="4" executed="2" coverage="50"/></class></file>');
        $file = $document->getElementsByTagName('file')->item(0);
        assert($file instanceof DOMElement);

        $index = new CoverageIndex();
        (new CoverageReader())->readMethods($file, 'src/B.php', $index);

        self::assertNull($index->methodAt('src/B.php', 0, 6));
        self::assertSame(50.0, $index->methodAt('src/B.php', 7, 9)?->percent);
        self::assertSame(4, $index->methodAt('src/B.php', 7, 9)->executable);
        self::assertSame(2, $index->methodAt('src/B.php', 7, 9)->executed);
    }

    public function testNormalizeTestIdStripsDataSetSuffixes(): void
    {
        $reader = new CoverageReader();

        self::assertSame('Tests\FooTest::testBar', $reader->normalizeTestId('Tests\FooTest::testBar with data set #0'));
        self::assertSame('Tests\FooTest::testBar', $reader->normalizeTestId('Tests\FooTest::testBar with data set "named case"'));
        self::assertSame('Tests\FooTest::testBar', $reader->normalizeTestId('Tests\FooTest::testBar'));
    }
}

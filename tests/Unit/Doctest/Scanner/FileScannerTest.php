<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Scanner;

use function iterator_to_array;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\Doctest\Configuration\Configuration;
use Toolkit\Doctest\Scanner\FileScanner;

/**
 * @covers \Toolkit\Doctest\Scanner\FileScanner
 * @uses \Toolkit\Doctest\Configuration\Configuration
 */
#[CoversClass(FileScanner::class)]
#[UsesClass(Configuration::class)]
final class FileScannerTest extends TestCase
{
    public function testScanWalksConfiguredDirectoriesAndSkipsMissingOnes(): void
    {
        $root = (string) realpath(__DIR__ . '/../../../Fixture/Doctest/project/src');
        $config = new Configuration(directories: ['/does/not/exist', $root]);

        $files = iterator_to_array((new FileScanner($config))->scan(), false);

        self::assertSame([$root . '/Calculator.php', $root . '/Nested/Excluded.php'], $files);
    }

    public function testScanHonoursExcludePatterns(): void
    {
        $root = (string) realpath(__DIR__ . '/../../../Fixture/Doctest/project/src');
        $config = new Configuration(directories: [$root], excludePatterns: ['*/Nested/*']);

        $files = iterator_to_array((new FileScanner($config))->scan(), false);

        self::assertSame([$root . '/Calculator.php'], $files);
    }

    public function testScanYieldsConfiguredFilesAndSkipsMissingOnes(): void
    {
        $file = (string) realpath(__DIR__ . '/../../../Fixture/Doctest/project/src/Calculator.php');
        $config = new Configuration(files: ['/does/not/exist.php', $file]);

        self::assertSame([$file], iterator_to_array((new FileScanner($config))->scan(), false));
    }

    public function testScanDirectoryReturnsTheSortedPhpFilesBelowIt(): void
    {
        $root = (string) realpath(__DIR__ . '/../../../Fixture/Doctest/project/src');
        $scanner = new FileScanner(new Configuration());

        $files = iterator_to_array($scanner->scanDirectory($root), false);

        self::assertSame([$root . '/Calculator.php', $root . '/Nested/Excluded.php'], $files);
    }

    public function testShouldIncludeAppliesThePatternsBothAnchoredAndUnanchored(): void
    {
        $scanner = new FileScanner(new Configuration(excludePatterns: ['*Test.php']));

        self::assertFalse($scanner->shouldInclude('/app/src/WidgetTest.php'));
        self::assertTrue($scanner->shouldInclude('/app/src/Widget.php'));
    }

    public function testShouldIncludeHonoursAPatternThatOnlyMatchesUnanchored(): void
    {
        $scanner = new FileScanner(new Configuration(excludePatterns: ['/Nested/*']));

        self::assertFalse($scanner->shouldInclude('/app/src/Nested/Excluded.php'));
        self::assertTrue($scanner->shouldInclude('/app/src/Widget.php'));
    }
}

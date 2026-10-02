<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Filesystem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Filesystem\DocGenPathResolver;
use Toolkit\DocGen\Filesystem\SourceFileFinder;

/**
 * @covers \Toolkit\DocGen\Filesystem\SourceFileFinder
 * @uses \Toolkit\DocGen\Filesystem\DocGenPathResolver
 */
#[CoversClass(SourceFileFinder::class)]
#[UsesClass(DocGenPathResolver::class)]
final class SourceFileFinderTest extends TestCase
{
    public function testFindReturnsPhpFilesRecursivelySorted(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-finder-' . uniqid('', true);
        mkdir($dir . '/src/Sub', 0777, true);
        file_put_contents($dir . '/src/B.php', '<?php');
        file_put_contents($dir . '/src/A.php', '<?php');
        file_put_contents($dir . '/src/Sub/C.php', '<?php');
        file_put_contents($dir . '/src/notes.txt', 'x');

        $files = (new SourceFileFinder())->find($dir . '/src', $dir, []);

        self::assertSame([
            $dir . '/src/A.php',
            $dir . '/src/B.php',
            $dir . '/src/Sub/C.php',
        ], $files);
    }

    public function testFindPrunesExcludedSubtree(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-finder-' . uniqid('', true);
        mkdir($dir . '/src/Keep', 0777, true);
        mkdir($dir . '/src/Generated/Nested', 0777, true);
        file_put_contents($dir . '/src/Keep/Kept.php', '<?php');
        file_put_contents($dir . '/src/Generated/Hidden.php', '<?php');
        file_put_contents($dir . '/src/Generated/Nested/AlsoHidden.php', '<?php');

        $files = (new SourceFileFinder())->find($dir . '/src', $dir, ['src/Generated']);

        self::assertSame([$dir . '/src/Keep/Kept.php'], $files);
    }

    public function testFindReturnsEmptyListForMissingDirectory(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-finder-' . uniqid('', true);

        self::assertSame([], (new SourceFileFinder())->find($dir . '/absent', $dir, []));
    }

    public function testExcludedMatchesRelativePathAgainstGlobs(): void
    {
        self::assertTrue((new SourceFileFinder())->excluded('src/Generated/File.php', ['src/Generated/*']));
        self::assertFalse((new SourceFileFinder())->excluded('src/Keep/File.php', ['src/Generated/*']));
        self::assertFalse((new SourceFileFinder())->excluded('src/Anything.php', []));
    }
}

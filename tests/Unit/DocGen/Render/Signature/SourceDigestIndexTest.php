<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Signature;

use PhpAiToolkit\DocGen\Render\Signature\SourceDigestIndex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SourceDigestIndex::class)]
final class SourceDigestIndexTest extends TestCase
{
    public function testOfFollowsTheContentOfAFile(): void
    {
        $root = sys_get_temp_dir() . '/docgen-digest-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/A.php', '<?php class A {}');
        file_put_contents($root . '/src/B.php', '<?php class A {}');

        self::assertSame((new SourceDigestIndex())->of($root, 'src/A.php'), (new SourceDigestIndex())->of($root, 'src/B.php'));

        file_put_contents($root . '/src/B.php', '<?php class B {}');

        self::assertNotSame((new SourceDigestIndex())->of($root, 'src/A.php'), (new SourceDigestIndex())->of($root, 'src/B.php'));
    }

    public function testOfReadsEachFileOncePerIndex(): void
    {
        $root = sys_get_temp_dir() . '/docgen-digest-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/A.php', '<?php class A {}');
        $index = new SourceDigestIndex();
        $first = $index->of($root, 'src/A.php');

        file_put_contents($root . '/src/A.php', '<?php class Changed {}');

        self::assertSame($first, $index->of($root, 'src/A.php'));
        self::assertNotSame($first, (new SourceDigestIndex())->of($root, 'src/A.php'));
    }

    public function testOfReportsAFileTheProjectDoesNotHave(): void
    {
        $root = sys_get_temp_dir() . '/docgen-digest-' . bin2hex(random_bytes(4));

        self::assertSame(SourceDigestIndex::MISSING, (new SourceDigestIndex())->of($root, 'src/Absent.php'));
    }
}

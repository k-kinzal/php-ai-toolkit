<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Filesystem;

use PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocGenPathResolver::class)]
final class DocGenPathResolverTest extends TestCase
{
    public function testResolveReturnsAbsolutePathUnchanged(): void
    {
        self::assertSame('/other/File.php', (new DocGenPathResolver())->resolve('/base', '/other/File.php'));
    }

    public function testResolveJoinsRelativePathToBase(): void
    {
        self::assertSame('/base/src/File.php', (new DocGenPathResolver())->resolve('/base', 'src/File.php'));
        self::assertSame('/base/src/File.php', (new DocGenPathResolver())->resolve('/base/', 'src/File.php'));
    }

    public function testResolveNormalizesBackslashes(): void
    {
        self::assertSame('C:/proj/src/File.php', (new DocGenPathResolver())->resolve('C:\\proj', 'src\\File.php'));
        self::assertSame('/abs/File.php', (new DocGenPathResolver())->resolve('/base', '\\abs\\File.php'));
    }

    public function testRelativeStripsBasePrefixFromInsidePath(): void
    {
        self::assertSame('src/File.php', (new DocGenPathResolver())->relative('/base', '/base/src/File.php'));
        self::assertSame('src/File.php', (new DocGenPathResolver())->relative('/base/', '/base/src/File.php'));
    }

    public function testRelativeReturnsOutsidePathUnchanged(): void
    {
        self::assertSame('/other/File.php', (new DocGenPathResolver())->relative('/base', '/other/File.php'));
    }

    public function testRelativeNormalizesBackslashes(): void
    {
        self::assertSame('src/File.php', (new DocGenPathResolver())->relative('C:\\proj', 'C:\\proj\\src\\File.php'));
    }
}

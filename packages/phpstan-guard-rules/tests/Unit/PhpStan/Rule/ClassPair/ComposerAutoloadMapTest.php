<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ClassPair;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ClassPair\ComposerAutoloadMap;

/**
 * @covers \Toolkit\PhpStan\Rule\ClassPair\ComposerAutoloadMap
 */
#[CoversClass(ComposerAutoloadMap::class)]
final class ComposerAutoloadMapTest extends TestCase
{
    public function testFromProjectRootReadsAutoloadAndAutoloadDevSections(): void
    {
        $map = ComposerAutoloadMap::fromProjectRoot(__DIR__ . '/../../../../../tests/PhpStan/ClassPair/Project');

        self::assertSame('Tests\PhpStan\ClassPair\Project\src', $map->namespaceForPath('src'));
        self::assertSame('Tests\PhpStan\ClassPair\Project\tests\Unit', $map->namespaceForPath('tests/Unit'));
    }

    public function testFromProjectRootWithoutComposerJsonHasNoEntries(): void
    {
        $map = ComposerAutoloadMap::fromProjectRoot(__DIR__ . '/../../../../../tests/PhpStan/ClassPair/Missing');

        self::assertNull($map->namespaceForPath('src'));
    }

    public function testFromProjectRootWithoutAutoloadSectionsHasNoEntries(): void
    {
        $map = ComposerAutoloadMap::fromProjectRoot(__DIR__ . '/../../../../../tests/PhpStan/ClassPair/Empty');

        self::assertNull($map->namespaceForPath('src'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function providerNormalizePath(): iterable
    {
        yield 'trailing slash' => ['src/', 'src'];
        yield 'current directory prefix' => ['./tests', 'tests'];
        yield 'backslashes' => ['src\Sub\\', 'src/Sub'];
        yield 'current directory' => ['.', ''];
        yield 'empty' => ['', ''];
    }

    /**
     * @dataProvider providerNormalizePath
     */
    #[DataProvider('providerNormalizePath')]
    public function testNormalizePathStripsCurrentDirectoryAndSeparators(string $path, string $expected): void
    {
        self::assertSame($expected, ComposerAutoloadMap::normalizePath($path));
    }

    public function testNamespaceForPathAppendsSubdirectoriesAsNamespaceSegments(): void
    {
        $map = new ComposerAutoloadMap([['App\\', 'src/'], ['Tests\\', './tests']]);

        self::assertSame('App', $map->namespaceForPath('src'));
        self::assertSame('Tests\Unit\Service', $map->namespaceForPath('tests/Unit/Service/'));
        self::assertNull($map->namespaceForPath('lib'));
    }

    public function testNamespaceForPathPrefersTheLongestDirectory(): void
    {
        $map = new ComposerAutoloadMap([['Root', ''], ['App', 'src'], ['App\Sub', 'src/Sub']]);

        self::assertSame('App\Sub\Deep', $map->namespaceForPath('src/Sub/Deep'));
        self::assertSame('Root\lib', $map->namespaceForPath('lib'));
    }

    public function testNamespaceForPathWithFallbackNamespaceReturnsRelativeNamespace(): void
    {
        $map = new ComposerAutoloadMap([['', 'src']]);

        self::assertSame('', $map->namespaceForPath('src'));
        self::assertSame('Sub', $map->namespaceForPath('src/Sub'));
    }

    public function testFileForClassMirrorsNamespaceSegmentsUnderTheDirectory(): void
    {
        $map = new ComposerAutoloadMap([['App', 'src'], ['Tests', 'tests']]);

        self::assertSame('src/Service/User.php', $map->fileForClass('App\Service\User'));
        self::assertSame('tests/Unit/UserTest.php', $map->fileForClass('Tests\Unit\UserTest'));
        self::assertNull($map->fileForClass('Other\Thing'));
    }

    public function testFileForClassPrefersTheLongestNamespace(): void
    {
        $map = new ComposerAutoloadMap([['', ''], ['App', 'src'], ['App\Sub', 'lib/sub']]);

        self::assertSame('lib/sub/Deep.php', $map->fileForClass('App\Sub\Deep'));
        self::assertSame('src/Other.php', $map->fileForClass('App\Other'));
        self::assertSame('Vendor/Thing.php', $map->fileForClass('Vendor\Thing'));
    }
}

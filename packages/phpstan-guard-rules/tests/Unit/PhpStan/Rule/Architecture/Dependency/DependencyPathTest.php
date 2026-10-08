<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Architecture\Dependency;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyPath;

/**
 * @covers \Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyPath
 */
#[CoversClass(DependencyPath::class)]
final class DependencyPathTest extends TestCase
{
    public function testMatchesAnchorsPatternsAndDistinguishesRecursiveGlobs(): void
    {
        $paths = new DependencyPath('/project');

        self::assertTrue($paths->matches('/project/examples/nested/demo.php', ['examples/**']));
        self::assertFalse($paths->matches('/project/vendor/package/examples/demo.php', ['examples/**']));
        self::assertFalse($paths->matches('/project/examples-backup/demo.php', ['examples/**']));
        self::assertFalse($paths->matches('/project/examples/nested/demo.php', ['examples/*.php']));
        self::assertTrue($paths->matches('/project/examples/demo.php', ['examples/**/*.php']));
        self::assertFalse($paths->matches('/project-other/examples/demo.php', ['**']));
    }

    public function testRelativePathsNormalizesParentSegments(): void
    {
        self::assertSame(['fixtures/input.json'], (new DependencyPath('/project'))->relativePaths('/project/examples/../fixtures/input.json'));
        self::assertSame([], (new DependencyPath('/project'))->relativePaths('phar:///project/examples/archive.phar'));
    }

    public function testNormalizeHandlesWindowsSeparatorsAndDotSegments(): void
    {
        self::assertSame('C:/project/fixtures/input.json', (new DependencyPath('C:/project'))->normalize('C:\\project\\examples\\..\\fixtures\\.\\input.json'));
    }

    public function testDisplayRemovesOnlyTheProjectRoot(): void
    {
        self::assertSame('examples/demo.php', (new DependencyPath('/project'))->display('/project/examples/demo.php'));
        self::assertSame('/elsewhere/demo.php', (new DependencyPath('/project'))->display('/elsewhere/demo.php'));
    }

    public function testSameRootDirectoryUsesTheFirstSegmentAndRejectsRootFiles(): void
    {
        $paths = new DependencyPath('/project');

        self::assertTrue($paths->sameRootDirectory('/project/tests/Unit/Test.php', '/project/tests/Integration/input.php'));
        self::assertFalse($paths->sameRootDirectory('/project/example/demo.php', '/project/examples/demo.php'));
        self::assertFalse($paths->sameRootDirectory('/project/tests/Test.php', '/project/tests/../fixtures/input.json'));
        self::assertFalse($paths->sameRootDirectory('/project/bootstrap.php', '/project/config.php'));
        self::assertFalse($paths->sameRootDirectory('/outside/tests/Test.php', '/project/tests/Test.php'));
    }

    public function testLocalFileRejectsRuntimeRelativePathsAndRemoteStreams(): void
    {
        $paths = new DependencyPath('/project');

        self::assertSame('/project/examples/demo.php', $paths->localFile('file:///project/examples/demo.php'));
        self::assertSame('C:\\project\\examples\\demo.php', $paths->localFile('C:\\project\\examples\\demo.php'));
        self::assertNull($paths->localFile('examples/demo.php'));
        self::assertNull($paths->localFile('https://example.org/examples/demo.php'));
        self::assertNull($paths->localFile("/project/examples/invalid\0.php"));
    }
}

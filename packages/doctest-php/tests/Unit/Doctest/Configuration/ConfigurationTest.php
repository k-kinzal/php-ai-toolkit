<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Configuration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\Doctest\Configuration\Configuration;

/**
 * @covers \Toolkit\Doctest\Configuration\Configuration
 */
#[CoversClass(Configuration::class)]
final class ConfigurationTest extends TestCase
{
    public function testGetDirectoriesIsEmptyByDefault(): void
    {
        self::assertSame([], (new Configuration())->getDirectories());
        self::assertSame(['/app/src'], (new Configuration(directories: ['/app/src']))->getDirectories());
    }

    public function testGetFilesIsEmptyByDefault(): void
    {
        self::assertSame([], (new Configuration())->getFiles());
        self::assertSame(['/app/helpers.php'], (new Configuration(files: ['/app/helpers.php']))->getFiles());
    }

    public function testGetExcludePatternsIsEmptyByDefault(): void
    {
        self::assertSame([], (new Configuration())->getExcludePatterns());
        self::assertSame(['*Test.php'], (new Configuration(excludePatterns: ['*Test.php']))->getExcludePatterns());
    }

    public function testGetBootstrapIsAbsentByDefault(): void
    {
        self::assertNull((new Configuration())->getBootstrap());
        self::assertSame('/app/boot.php', (new Configuration(bootstrap: '/app/boot.php'))->getBootstrap());
    }

    public function testIsEnabledIsTrueByDefault(): void
    {
        self::assertTrue((new Configuration())->isEnabled());
        self::assertFalse((new Configuration(enabled: false))->isEnabled());
    }

    public function testHasSourcesReportsWhetherAnythingIsSelected(): void
    {
        self::assertFalse((new Configuration())->hasSources());
        self::assertTrue((new Configuration(directories: ['/app/src']))->hasSources());
        self::assertTrue((new Configuration(files: ['/app/a.php']))->hasSources());
    }

    public function testResolvePathJoinsRelativePathsAndKeepsAbsoluteOnes(): void
    {
        self::assertSame('/app/src', Configuration::resolvePath('src', '/app'));
        self::assertSame('/app/src', Configuration::resolvePath('src', '/app/'));
        self::assertSame('src', Configuration::resolvePath('src', ''));
        self::assertSame('/opt/src', Configuration::resolvePath('/opt/src', '/app'));
    }
}

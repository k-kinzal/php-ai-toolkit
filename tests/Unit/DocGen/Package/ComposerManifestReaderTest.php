<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Package;

use PhpAiToolkit\DocGen\Config\RepositoryUrl;
use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\ComposerManifestReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ComposerManifestReader::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(RepositoryUrl::class)]
final class ComposerManifestReaderTest extends TestCase
{
    public function testReadParsesFullManifest(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-manifest-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/composer.json', <<<'JSON'
{
    "name": "acme/full",
    "description": "Full manifest.",
    "autoload": {"psr-4": {"Acme\\Full\\": "src/"}, "classmap": ["lib/legacy/"]},
    "autoload-dev": {"psr-4": {"Acme\\Full\\Tests\\": ["tests/", "extra"]}, "classmap": ["tests/Fixture/"]},
    "require": {"php": ">=8.0", "acme/dep": "^1.0"},
    "require-dev": {"phpunit/phpunit": "^11.0"},
    "suggest": {"acme/extra": "Adds extra features"},
    "homepage": "https://acme.example.com",
    "support": {"source": "https://github.com/acme/full/"}
}
JSON);

        $manifest = (new ComposerManifestReader())->read($dir . '/composer.json');

        self::assertSame($dir, $manifest->directory);
        self::assertSame('acme/full', $manifest->name);
        self::assertSame('Full manifest.', $manifest->description);
        self::assertSame(['Acme\\Full\\' => ['src']], $manifest->autoload);
        self::assertSame(['Acme\\Full\\Tests\\' => ['tests', 'extra']], $manifest->devAutoload);
        self::assertSame(['php' => '>=8.0', 'acme/dep' => '^1.0'], $manifest->requires);
        self::assertSame(['phpunit/phpunit' => '^11.0'], $manifest->devRequires);
        self::assertSame(['acme/extra' => 'Adds extra features'], $manifest->suggests);
        self::assertSame(['lib/legacy'], $manifest->classmap);
        self::assertSame(['tests/Fixture'], $manifest->devClassmap);
        self::assertSame('https://github.com/acme/full', $manifest->repository);
    }

    public function testReadFallsBackToDirectoryBasenameWhenNameIsMissing(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-manifest-' . uniqid('', true);
        mkdir($dir . '/fallback-pkg', 0777, true);
        file_put_contents($dir . '/fallback-pkg/composer.json', '{}');

        $manifest = (new ComposerManifestReader())->read($dir . '/fallback-pkg/composer.json');

        self::assertSame('fallback-pkg', $manifest->name);
        self::assertSame('', $manifest->description);
        self::assertSame([], $manifest->autoload);
        self::assertSame([], $manifest->devAutoload);
        self::assertSame([], $manifest->requires);
        self::assertSame([], $manifest->devRequires);
        self::assertSame([], $manifest->suggests);
        self::assertSame([], $manifest->classmap);
        self::assertSame([], $manifest->devClassmap);
        self::assertSame('', $manifest->repository);
    }

    public function testReadNormalizesPsr4StringAndArrayPaths(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-manifest-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/composer.json', <<<'JSON'
{
    "name": "acme/paths",
    "autoload": {"psr-4": {"A\\": "src/", "B\\": ["lib/", "deep/dir/"]}}
}
JSON);

        $manifest = (new ComposerManifestReader())->read($dir . '/composer.json');

        self::assertSame(['A\\' => ['src'], 'B\\' => ['lib', 'deep/dir']], $manifest->autoload);
    }

    public function testReadRejectsMissingManifest(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-manifest-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Composer manifest not found: ' . $dir . '/composer.json');

        (new ComposerManifestReader())->read($dir . '/composer.json');
    }

    #[RunInSeparateProcess]
    public function testReadRejectsUnreadableManifest(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-manifest-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/composer.json', '{"name": "acme/locked"}');
        chmod($dir . '/composer.json', 0000);
        set_error_handler(static fn (): bool => true);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Composer manifest is not readable: ' . $dir . '/composer.json');

        (new ComposerManifestReader())->read($dir . '/composer.json');
    }

    public function testReadRejectsInvalidJson(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-manifest-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/composer.json', '{invalid');

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid composer.json at ' . $dir . '/composer.json: Syntax error');

        (new ComposerManifestReader())->read($dir . '/composer.json');
    }

    public function testReadRejectsNonObjectJson(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-manifest-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/composer.json', '"just a string"');

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid composer.json at ' . $dir . '/composer.json: No error');

        (new ComposerManifestReader())->read($dir . '/composer.json');
    }

    public function testPsr4MapReturnsEmptyMapForMissingPsr4Section(): void
    {
        $reader = new ComposerManifestReader();

        self::assertSame([], $reader->psr4Map(null));
        self::assertSame([], $reader->psr4Map('files'));
        self::assertSame([], $reader->psr4Map(['psr-0' => ['A_' => 'src']]));
        self::assertSame([], $reader->psr4Map(['psr-4' => 'src']));
    }

    public function testPsr4MapNormalizesPathsAndDropsInvalidEntries(): void
    {
        $map = (new ComposerManifestReader())->psr4Map([
            'psr-4' => [
                'A\\' => 'src\\sub\\',
                'B\\' => [7, 'lib/'],
                'C\\' => 7,
            ],
        ]);

        self::assertSame(['A\\' => ['src/sub'], 'B\\' => ['lib']], $map);
    }

    public function testPsr4MapKeepsPackageRootPathAsEmptyString(): void
    {
        $map = (new ComposerManifestReader())->psr4Map(['psr-4' => ['Symfony\\Component\\Yaml\\' => '', 'Acme\\' => '/']]);

        self::assertSame(['Symfony\\Component\\Yaml\\' => [''], 'Acme\\' => ['']], $map);
    }

    public function testClassmapListReturnsEmptyListForMissingClassmapSection(): void
    {
        $reader = new ComposerManifestReader();

        self::assertSame([], $reader->classmapList(null));
        self::assertSame([], $reader->classmapList('src'));
        self::assertSame([], $reader->classmapList(['psr-4' => ['A\\' => 'src']]));
        self::assertSame([], $reader->classmapList(['classmap' => 'src']));
    }

    public function testClassmapListNormalizesPathsAndDropsInvalidEntries(): void
    {
        $paths = (new ComposerManifestReader())->classmapList([
            'classmap' => ['src\\legacy\\', 'Legacy.php', '', 7, '/'],
        ]);

        self::assertSame(['src/legacy', 'Legacy.php'], $paths);
    }

    public function testConstraintMapKeepsOnlyStringConstraints(): void
    {
        $map = (new ComposerManifestReader())->constraintMap([
            'acme/a' => '^1.0',
            'acme/b' => 2,
            'acme/c' => ['^3.0'],
        ]);

        self::assertSame(['acme/a' => '^1.0'], $map);
    }

    public function testConstraintMapReturnsEmptyMapForNonArraySection(): void
    {
        $reader = new ComposerManifestReader();

        self::assertSame([], $reader->constraintMap(null));
        self::assertSame([], $reader->constraintMap('^1.0'));
    }

    public function testRepositoryPrefersTheDeclaredSourceOverTheHomepage(): void
    {
        $repository = (new ComposerManifestReader())->repository([
            'homepage' => 'https://acme.example.com',
            'support' => ['source' => 'https://github.com/acme/lib'],
        ]);

        self::assertSame('https://github.com/acme/lib', $repository);
    }

    public function testRepositoryFallsBackToTheHomepageAndThenToNothing(): void
    {
        $reader = new ComposerManifestReader();

        self::assertSame('https://github.com/acme/lib', $reader->repository(['homepage' => 'https://github.com/acme/lib']));
        self::assertSame('https://github.com/acme/lib', $reader->repository(['support' => 'issues@acme.example.com', 'homepage' => 'https://github.com/acme/lib']));
        self::assertSame('', $reader->repository(['support' => ['source' => 'git@github.com:acme/lib.git']]));
        self::assertSame('', $reader->repository(['name' => 'acme/lib']));
        self::assertSame('', $reader->repository('acme/lib'));
    }
}

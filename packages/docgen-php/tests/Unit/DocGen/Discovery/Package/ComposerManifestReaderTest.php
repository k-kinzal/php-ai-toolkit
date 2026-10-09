<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Discovery\Package;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Action\Config\RepositoryUrl;
use Toolkit\DocGen\Discovery\Package\ComposerManifest;
use Toolkit\DocGen\Discovery\Package\ComposerManifestReader;
use Toolkit\DocGen\Discovery\Package\RepositoryAddress;
use Toolkit\DocGen\DocGenException;

/**
 * @covers \Toolkit\DocGen\Discovery\Package\ComposerManifestReader
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Action\Config\RepositoryUrl
 * @uses \Toolkit\DocGen\Discovery\Package\RepositoryAddress
 */
#[CoversClass(ComposerManifestReader::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(RepositoryUrl::class)]
#[UsesClass(RepositoryAddress::class)]
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

    public function testContentsRejectsMissingManifest(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-manifest-' . uniqid('', true);
        mkdir($dir, 0777, true);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Composer manifest not found: ' . $dir . '/composer.json');

        (new ComposerManifestReader())->contents($dir . '/composer.json');
    }

    #[RunInSeparateProcess]
    public function testContentsRejectsUnreadableManifest(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-manifest-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/composer.json', '{"name": "acme/locked"}');
        chmod($dir . '/composer.json', 0000);
        set_error_handler(static fn (): bool => true);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Composer manifest is not readable: ' . $dir . '/composer.json');

        (new ComposerManifestReader())->contents($dir . '/composer.json');
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

    /**
     * @dataProvider providerManifestSections
     * @param array<string, list<string>> $autoload
     * @param list<string> $classmap
     * @param array<string, string> $requires
     */
    #[DataProvider('providerManifestSections')]
    public function testReadNormalizesManifestSections(string $json, array $autoload, array $classmap, array $requires, string $repository): void
    {
        $dir = sys_get_temp_dir() . '/docgen-manifest-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/composer.json', $json);

        try {
            $manifest = (new ComposerManifestReader())->read($dir . '/composer.json');

            self::assertSame($autoload, $manifest->autoload);
            self::assertSame($classmap, $manifest->classmap);
            self::assertSame($requires, $manifest->requires);
            self::assertSame($repository, $manifest->repository);
        } finally {
            unlink($dir . '/composer.json');
            rmdir($dir);
        }
    }

    /**
     * @return array<string, array{string, array<string, list<string>>, list<string>, array<string, string>, string}>
     */
    public static function providerManifestSections(): array
    {
        return [
            'null autoload' => ['{"autoload": null}', [], [], [], ''],
            'scalar autoload' => ['{"autoload": "src"}', [], [], [], ''],
            'unrecognized autoload' => ['{"autoload": {"psr-0": {"A_": "src"}}}', [], [], [], ''],
            'scalar PSR-4' => ['{"autoload": {"psr-4": "src"}}', [], [], [], ''],
            'scalar classmap' => ['{"autoload": {"classmap": "src"}}', [], [], [], ''],
            'invalid PSR-4 entries' => [<<<'JSON'
{"autoload": {"psr-4": {"A\\": "src\\sub\\", "B\\": [7, "lib/"], "C\\": 7}}}
JSON, ['A\\' => ['src/sub'], 'B\\' => ['lib']], [], [], ''],
            'PSR-4 package roots' => [<<<'JSON'
{"autoload": {"psr-4": {"Symfony\\Component\\Yaml\\": "", "Acme\\": "/"}}}
JSON, ['Symfony\\Component\\Yaml\\' => [''], 'Acme\\' => ['']], [], [], ''],
            'invalid classmap entries' => [<<<'JSON'
{"autoload": {"classmap": ["src\\legacy\\", "Legacy.php", "", 7, "/"]}}
JSON, [], ['src/legacy', 'Legacy.php'], [], ''],
            'invalid constraints' => ['{"require": {"acme/a": "^1.0", "acme/b": 2, "acme/c": ["^3.0"]}}', [], [], ['acme/a' => '^1.0'], ''],
            'null constraints' => ['{"require": null}', [], [], [], ''],
            'scalar constraints' => ['{"require": "^1.0"}', [], [], [], ''],
            'source before homepage' => ['{"homepage": "https://acme.example.com", "support": {"source": "https://github.com/acme/lib"}}', [], [], [], 'https://github.com/acme/lib'],
            'homepage without source' => ['{"homepage": "https://github.com/acme/lib"}', [], [], [], 'https://github.com/acme/lib'],
            'scalar support' => ['{"support": "issues@acme.example.com", "homepage": "https://github.com/acme/lib"}', [], [], [], 'https://github.com/acme/lib'],
            'unbrowsable source' => ['{"support": {"source": "git@github.com:acme/lib.git"}}', [], [], [], ''],
            'missing repository' => ['{"name": "acme/lib"}', [], [], [], ''],
        ];
    }

    public function testPathsNormalizesSeparatorsAndHandlesPackageRoots(): void
    {
        $reader = new ComposerManifestReader();

        self::assertSame(['src/legacy', '', '', 'lib'], $reader->paths(['src\\legacy\\', '', '/', 'lib/'], true));
        self::assertSame(['src/legacy', 'lib'], $reader->paths(['src\\legacy\\', '', '/', 'lib/'], false));
    }
}

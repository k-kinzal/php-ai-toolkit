<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Package;

use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Package\ComposerManifest
 */
#[CoversClass(ComposerManifest::class)]
final class ComposerManifestTest extends TestCase
{
    public function testStoresManifestData(): void
    {
        $manifest = new ComposerManifest(
            '/projects/lib',
            'acme/lib',
            'Acme library.',
            ['Acme\\Lib\\' => ['src']],
            ['Acme\\Lib\\Tests\\' => ['tests']],
            ['php' => '>=8.0'],
            ['phpunit/phpunit' => '^11.0'],
            ['acme/extra' => 'Adds extra features'],
            ['lib/legacy'],
            ['tests/Fixture'],
            'https://github.com/acme/lib',
        );

        self::assertSame('/projects/lib', $manifest->directory);
        self::assertSame('acme/lib', $manifest->name);
        self::assertSame('Acme library.', $manifest->description);
        self::assertSame(['Acme\\Lib\\' => ['src']], $manifest->autoload);
        self::assertSame(['Acme\\Lib\\Tests\\' => ['tests']], $manifest->devAutoload);
        self::assertSame(['php' => '>=8.0'], $manifest->requires);
        self::assertSame(['phpunit/phpunit' => '^11.0'], $manifest->devRequires);
        self::assertSame(['acme/extra' => 'Adds extra features'], $manifest->suggests);
        self::assertSame(['lib/legacy'], $manifest->classmap);
        self::assertSame(['tests/Fixture'], $manifest->devClassmap);
        self::assertSame('https://github.com/acme/lib', $manifest->repository);
    }

    public function testStoresEmptyMapsForBareManifest(): void
    {
        $manifest = new ComposerManifest('/projects/bare', 'acme/bare', '', [], [], [], [], []);

        self::assertSame('/projects/bare', $manifest->directory);
        self::assertSame('acme/bare', $manifest->name);
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
}

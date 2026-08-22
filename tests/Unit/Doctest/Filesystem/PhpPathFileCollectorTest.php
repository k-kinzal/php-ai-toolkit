<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Filesystem;

use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PhpAiToolkit\Doctest\DoctestException;
use PhpAiToolkit\Doctest\Filesystem\DoctestPathResolver;
use PhpAiToolkit\Doctest\Filesystem\PhpFileInclusionPolicy;
use PhpAiToolkit\Doctest\Filesystem\PhpPathFileCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpPathFileCollector::class)]
#[UsesClass(DoctestConfig::class)]
#[UsesClass(DoctestPathResolver::class)]
#[UsesClass(PhpFileInclusionPolicy::class)]
final class PhpPathFileCollectorTest extends TestCase
{
    public function testFilesCollectsAWholeDirectoryAndHonoursExclusions(): void
    {
        $root = realpath(__DIR__ . '/../../../Fixture/Doctest/project');
        $config = new DoctestConfig((string) $root, ['src'], ['src/Nested/*']);

        $files = (new PhpPathFileCollector())->files($config, $root . '/src');

        self::assertSame(['src/Calculator.php'], array_values($files));
    }

    public function testFilesCollectsASingleFileAndSkipsAnExcludedOne(): void
    {
        $root = realpath(__DIR__ . '/../../../Fixture/Doctest/project');
        $config = new DoctestConfig((string) $root, ['src'], ['src/Nested/*']);
        $collector = new PhpPathFileCollector();

        self::assertSame(['src/Calculator.php'], array_values($collector->files($config, $root . '/src/Calculator.php')));
        self::assertSame([], $collector->files($config, $root . '/src/Nested/Excluded.php'));
    }

    public function testFilesRejectsAPathThatDoesNotExist(): void
    {
        $config = new DoctestConfig('/app', ['src'], []);

        $this->expectException(DoctestException::class);
        $this->expectExceptionMessage('Configured path does not exist: /app/missing');

        (new PhpPathFileCollector())->files($config, '/app/missing');
    }
}

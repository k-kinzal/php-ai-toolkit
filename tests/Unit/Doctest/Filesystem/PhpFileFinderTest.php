<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Filesystem;

use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PhpAiToolkit\Doctest\Filesystem\DoctestPathResolver;
use PhpAiToolkit\Doctest\Filesystem\PhpFileFinder;
use PhpAiToolkit\Doctest\Filesystem\PhpFileInclusionPolicy;
use PhpAiToolkit\Doctest\Filesystem\PhpPathFileCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpFileFinder::class)]
#[UsesClass(DoctestConfig::class)]
#[UsesClass(DoctestPathResolver::class)]
#[UsesClass(PhpFileInclusionPolicy::class)]
#[UsesClass(PhpPathFileCollector::class)]
final class PhpFileFinderTest extends TestCase
{
    public function testFindReturnsSortedAbsoluteToRelativePaths(): void
    {
        $root = realpath(__DIR__ . '/../../../Fixture/Doctest/project');
        $config = new DoctestConfig((string) $root, ['src'], ['src/Nested/*']);

        $files = (new PhpFileFinder())->find($config);

        self::assertSame([$root . '/src/Calculator.php' => 'src/Calculator.php'], $files);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use FilesystemIterator;
use Guard\Cli\BaselineFile;
use Guard\Diagnostic\PolicyException;
use Guard\Reporting\Baseline;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * @covers \Guard\Cli\BaselineFile
 * @uses \Guard\Reporting\Baseline
 * @uses \Guard\Config\Schema
 * @uses \Guard\Diagnostic\PolicyException
 */
#[\PHPUnit\Framework\Attributes\CoversClass(BaselineFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Baseline::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
final class BaselineFileTest extends \PHPUnit\Framework\TestCase
{
    public function testPathResolvesFromTheSelectedPolicyDirectory(): void
    {
        $file = new BaselineFile();
        self::assertSame('/project/config/guard-baseline.json', $file->path('/project/config/guard.yaml', null));
        self::assertSame('/project/config/custom.json', $file->path('/project/config/guard.yaml', 'custom.json'));
        self::assertSame('/tmp/custom.json', $file->path('/project/config/guard.yaml', '/tmp/custom.json'));
        $this->expectException(PolicyException::class);
        $file->path('/project/guard.yaml', ' ');
    }

    public function testReadRejectsAnExplicitMissingFile(): void
    {
        $this->expectException(PolicyException::class);
        (new BaselineFile())->read('/guard-missing-' . uniqid() . '/baseline.json');
    }

    /**
     * @throws JsonException
     */
    public function testWriteCreatesAndUpdatesOnlyBaselinesWithoutTemporaryFiles(): void
    {
        $project = new class () {
            public string $root;
            /**
             * @param array<array-key, string> $files
             */
            public function __construct(array $files = [])
            {
                $this->root = sys_get_temp_dir() . '/guard-contract-' . uniqid('', true);
                mkdir($this->root);
                foreach ($files as $path => $source) {
                    $this->write((string) $path, $source);
                }
            }
            public function write(string $path, string $source): void
            {
                $directory = dirname($this->root . '/' . $path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                file_put_contents($this->root . '/' . $path, $source);
            }

            /**
             * @return array<array-key, string>
             * @throws RuntimeException
             */
            public function files(): array
            {
                $files = [];
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS)) as $file) {
                    if ($file instanceof SplFileInfo && $file->isFile()) {
                        $source = file_get_contents($file->getPathname());
                        if ($source === false) {
                            throw new RuntimeException('Cannot read test fixture ' . $file->getPathname());
                        }
                        $files[substr($file->getPathname(), strlen($this->root) + 1)] = $source;
                    }
                }
                ksort($files, SORT_STRING);
                return $files;
            }
            public function remove(): void
            {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if ($file instanceof SplFileInfo) {
                        if ($file->isDir() && !$file->isLink()) {
                            rmdir($file->getPathname());
                        } else {
                            unlink($file->getPathname());
                        }
                    }
                }
                rmdir($this->root);
            }
        };
        try {
            $file = new BaselineFile();
            $path = $project->root . '/guard-baseline.json';
            $json = (new Baseline())->capture([]);
            $file->write($path, $json);
            self::assertSame($json, $file->read($path));
            $file->write($path, $json);
            self::assertSame(['guard-baseline.json' => $json], $project->files());
        } finally {
            $project->remove();
        }
    }

    /**
     * @throws JsonException
     */
    public function testWriteRefusesToOverwriteAnUnrelatedJsonFile(): void
    {
        $project = new class (['composer.json' => '{"name":"example/project"}']) {
            public string $root;
            /**
             * @param array<array-key, string> $files
             */
            public function __construct(array $files = [])
            {
                $this->root = sys_get_temp_dir() . '/guard-contract-' . uniqid('', true);
                mkdir($this->root);
                foreach ($files as $path => $source) {
                    $this->write((string) $path, $source);
                }
            }
            public function write(string $path, string $source): void
            {
                $directory = dirname($this->root . '/' . $path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                file_put_contents($this->root . '/' . $path, $source);
            }


            public function remove(): void
            {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if ($file instanceof SplFileInfo) {
                        if ($file->isDir() && !$file->isLink()) {
                            rmdir($file->getPathname());
                        } else {
                            unlink($file->getPathname());
                        }
                    }
                }
                rmdir($this->root);
            }
        };
        try {
            $this->expectException(PolicyException::class);
            (new BaselineFile())->write($project->root . '/composer.json', (new Baseline())->capture([]));
        } finally {
            self::assertSame('{"name":"example/project"}', file_get_contents($project->root . '/composer.json'));
            $project->remove();
        }
    }

    public function testValidateTargetRejectsSymlinksWithoutChangingTheirTargets(): void
    {
        $project = new class (['real.json' => '{"version":1,"entries":[]}']) {
            public string $root;
            /**
             * @param array<array-key, string> $files
             */
            public function __construct(array $files = [])
            {
                $this->root = sys_get_temp_dir() . '/guard-contract-' . uniqid('', true);
                mkdir($this->root);
                foreach ($files as $path => $source) {
                    $this->write((string) $path, $source);
                }
            }
            public function write(string $path, string $source): void
            {
                $directory = dirname($this->root . '/' . $path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                file_put_contents($this->root . '/' . $path, $source);
            }


            public function remove(): void
            {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if ($file instanceof SplFileInfo) {
                        if ($file->isDir() && !$file->isLink()) {
                            rmdir($file->getPathname());
                        } else {
                            unlink($file->getPathname());
                        }
                    }
                }
                rmdir($this->root);
            }
        };
        try {
            symlink($project->root . '/real.json', $project->root . '/link.json');
            $this->expectException(PolicyException::class);
            (new BaselineFile())->validateTarget($project->root . '/link.json');
        } finally {
            self::assertSame('{"version":1,"entries":[]}', file_get_contents($project->root . '/real.json'));
            $project->remove();
        }
    }

    public function testCurrentSeesCreationAndDeletionBetweenReads(): void
    {
        $project = new class () {
            public string $root;
            /**
             * @param array<array-key, string> $files
             */
            public function __construct(array $files = [])
            {
                $this->root = sys_get_temp_dir() . '/guard-contract-' . uniqid('', true);
                mkdir($this->root);
                foreach ($files as $path => $source) {
                    $this->write((string) $path, $source);
                }
            }
            public function write(string $path, string $source): void
            {
                $directory = dirname($this->root . '/' . $path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                file_put_contents($this->root . '/' . $path, $source);
            }


            public function remove(): void
            {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if ($file instanceof SplFileInfo) {
                        if ($file->isDir() && !$file->isLink()) {
                            rmdir($file->getPathname());
                        } else {
                            unlink($file->getPathname());
                        }
                    }
                }
                rmdir($this->root);
            }
        };
        try {
            $file = new BaselineFile();
            $path = $project->root . '/baseline.json';
            self::assertNull($file->current($path));
            $project->write('baseline.json', 'first');
            self::assertSame('first', $file->current($path));
            unlink($path);
            self::assertNull($file->current($path));
        } finally {
            $project->remove();
        }
    }
    public function testValidateNamesTheBrokenFileAndTheRecoveryAction(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid baseline project/custom.json: Syntax error Correct this file');
        (new BaselineFile())->validate('{', 'project/custom.json');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function is_dir;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

/**
 * Manages temporary directories for test isolation.
 */
final class TempDir
{
    /**
     * Absolute path to the temporary directory root.
     *
     * @var non-empty-string
     */
    public readonly string $path;

    /**
     * Absolute path to the simulated project root.
     */
    public readonly string $projectRoot;

    /**
     * Absolute path to the simulated package root.
     */
    public readonly string $packageRoot;

    /**
     * Creates a unique temporary directory with project and package subdirectories.
     */
    public function __construct()
    {
        $this->path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        $this->projectRoot = $this->path . '/project';
        $this->packageRoot = $this->path . '/package';

        mkdir($this->projectRoot, 0755, true);
        mkdir($this->packageRoot, 0755, true);
    }

    /**
     * Removes the temporary directory and all its contents.
     */
    public function cleanup(): void
    {
        if (!is_dir($this->path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->isLink() || !$item->isDir()) {
                unlink($item->getPathname());
            } else {
                rmdir($item->getPathname());
            }
        }

        rmdir($this->path);
    }
}

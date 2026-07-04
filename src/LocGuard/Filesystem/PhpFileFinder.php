<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Filesystem;

use function fnmatch;
use function is_dir;
use function is_file;
use function ksort;

use PhpAiToolkit\LocGuard\Config\LocGuardConfig;
use PhpAiToolkit\LocGuard\LocGuardException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function rtrim;

use SplFileInfo;

use function sprintf;
use function str_ends_with;
use function str_replace;
use function strlen;
use function substr;

/**
 * Finds PHP files from configured source paths.
 */
final class PhpFileFinder
{
    /**
     * @return array<string, string> map of absolute path to relative path
     */
    public function find(LocGuardConfig $config): array
    {
        $files = [];
        foreach ($config->paths as $path) {
            $absolutePath = $this->absolutePath($config->root, $path);
            $files += $this->findInPath($config, $absolutePath);
        }

        ksort($files);

        return $files;
    }

    private function absolutePath(string $root, string $path): string
    {
        if (str_starts_with($path, '/')) {
            return rtrim($path, '/');
        }

        return rtrim($root . '/' . $path, '/');
    }

    /**
     * @return array<string, string>
     */
    private function findInPath(LocGuardConfig $config, string $path): array
    {
        if (is_file($path)) {
            return $this->includeFile($config, $path) ? [$path => $this->relativePath($config->root, $path)] : [];
        }

        if (!is_dir($path)) {
            throw new LocGuardException(sprintf('Configured path does not exist: %s', $path));
        }

        return $this->findInDirectory($config, $path);
    }

    /**
     * @return array<string, string>
     */
    private function findInDirectory(LocGuardConfig $config, string $path): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            $file = $item->getPathname();
            if ($this->includeFile($config, $file)) {
                $files[$file] = $this->relativePath($config->root, $file);
            }
        }

        return $files;
    }

    private function includeFile(LocGuardConfig $config, string $path): bool
    {
        $relativePath = $this->relativePath($config->root, $path);
        if (!str_ends_with($relativePath, '.php')) {
            return false;
        }

        foreach ($config->exclude as $pattern) {
            if (fnmatch($pattern, $relativePath)) {
                return false;
            }
        }

        return true;
    }

    private function relativePath(string $root, string $path): string
    {
        $root = rtrim(str_replace('\\', '/', $root), '/') . '/';
        $path = str_replace('\\', '/', $path);

        return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    }
}

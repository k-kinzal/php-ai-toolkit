<?php

declare(strict_types=1);

namespace Tests\Support;

use FilesystemIterator;
use Guard\Config\Configuration;
use Guard\Config\ConfigurationLoader;
use Guard\Execution\Context;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * Temporary project with cleanup for end-to-end guard contracts.

 */
final class Project
{
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
     * @throws JsonException
     */
    public function context(bool $repair = false): Context
    {
        $path = $this->root . '/guard.yaml';
        $config = is_file($path) ? (new ConfigurationLoader())->load($path) : new Configuration($this->root, null, null, null, []);
        return new Context($config, $path, $repair);
    }
    /**
     * @return array<array-key, string>
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
}

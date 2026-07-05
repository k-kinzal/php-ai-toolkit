<?php

declare(strict_types=1);

namespace PhpAiToolkit\Installer\Cli\Command;

use function copy;
use function is_dir;
use function is_link;
use function mkdir;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function rmdir;

use SplFileInfo;

use function symlink;
use function unlink;

/**
 * Performs filesystem operations needed by skill installation.
 */
final class SkillFilesystemOperator
{
    /**
     * Ensures that the target directory exists.
     */
    public function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Removes an existing symlink or directory target.
     */
    public function remove(string $path): void
    {
        if (is_link($path)) {
            unlink($path);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
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

        rmdir($path);
    }

    /**
     * Recursively copies a skill directory.
     */
    public function copyDirectory(string $source, string $dest): bool
    {
        if (!mkdir($dest, 0755, true)) {
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            /** @var RecursiveDirectoryIterator $innerIterator */
            $innerIterator = $iterator->getInnerIterator();
            $targetPath = $dest . '/' . $innerIterator->getSubPathname();

            if ($item->isDir()) {
                if (!is_dir($targetPath) && !mkdir($targetPath, 0755, true)) {
                    return false;
                }
            } elseif (!copy($item->getPathname(), $targetPath)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Creates a symlink for an installed skill.
     */
    public function symlink(string $target, string $link): bool
    {
        return @symlink($target, $link);
    }
}

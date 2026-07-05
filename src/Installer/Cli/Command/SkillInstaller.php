<?php

declare(strict_types=1);

namespace PhpAiToolkit\Installer\Cli\Command;

use function is_dir;
use function is_link;

use PhpAiToolkit\Installer\RelativePathResolver;

use function sprintf;

/**
 * Installs one skill into one agent skill directory.
 */
final class SkillInstaller
{
    /**
     * Creates an installer from filesystem operations and output writing.
     */
    public function __construct(
        /** @readonly */
        private SkillFilesystemOperator $filesystemOperator,
        /** @readonly */
        private SkillInstallationWriter $writer,
    ) {
    }

    /**
     * Installs one skill and returns the result counter key.
     *
     * @return 'installed'|'skipped'|'errors'
     */
    public function install(
        string $skillsSource,
        string $absoluteTarget,
        string $skillName,
        bool $force,
        bool $copy,
    ): string {
        $targetPath = $absoluteTarget . '/' . $skillName;
        $sourcePath = $skillsSource . '/' . $skillName;

        if ((is_link($targetPath) || is_dir($targetPath)) && !$force) {
            $this->writer->write(sprintf('    [SKIP] %s (already exists, use --force to overwrite)', $skillName));

            return 'skipped';
        }

        if (is_link($targetPath) || is_dir($targetPath)) {
            $this->filesystemOperator->remove($targetPath);
        }

        if ($copy) {
            if ($this->filesystemOperator->copyDirectory($sourcePath, $targetPath)) {
                $this->writer->write(sprintf('    [OK] %s (copied)', $skillName));

                return 'installed';
            }

            $this->writer->write(sprintf('    [ERROR] %s (copy failed)', $skillName));

            return 'errors';
        }

        $relativePath = RelativePathResolver::relativePath($absoluteTarget, $sourcePath);
        if ($this->filesystemOperator->symlink($relativePath, $targetPath)) {
            $this->writer->write(sprintf('    [OK] %s -> %s', $skillName, $relativePath));

            return 'installed';
        }

        $this->writer->write(sprintf('    [ERROR] %s (symlink failed, try --copy instead)', $skillName));

        return 'errors';
    }
}

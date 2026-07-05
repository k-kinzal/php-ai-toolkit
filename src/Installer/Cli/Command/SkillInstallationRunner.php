<?php

declare(strict_types=1);

namespace PhpAiToolkit\Installer\Cli\Command;

use function sprintf;

/**
 * Runs skill installation across all detected agent directories.
 */
final class SkillInstallationRunner
{
    /**
     * Creates a runner from filesystem operations, skill installation, and output writing.
     */
    public function __construct(
        private readonly SkillFilesystemOperator $filesystemOperator,
        private readonly SkillInstaller $skillInstaller,
        private readonly SkillInstallationWriter $writer,
    ) {
    }

    /**
     * Installs all skills into all target agent skill directories.
     *
     * @param list<string> $skillDirs
     * @param array<string, string> $targetDirs
     * @return array{installed: int, skipped: int, errors: int}
     */
    public function install(
        string $projectRoot,
        string $skillsSource,
        array $skillDirs,
        array $targetDirs,
        bool $force,
        bool $copy,
    ): array {
        $stats = ['installed' => 0, 'skipped' => 0, 'errors' => 0];

        foreach ($targetDirs as $agentName => $skillsTarget) {
            $this->writer->write(sprintf('  [%s]', $agentName));

            $absoluteTarget = $projectRoot . '/' . $skillsTarget;
            $this->filesystemOperator->ensureDirectory($absoluteTarget);

            foreach ($skillDirs as $skillName) {
                $result = $this->skillInstaller->install($skillsSource, $absoluteTarget, $skillName, $force, $copy);
                $stats[$result]++;
            }
        }

        return $stats;
    }
}

<?php

declare(strict_types=1);

namespace PhpAiToolkit\Installer\Cli\Command;

use function array_diff;

use Closure;

use function copy;
use function is_dir;
use function is_link;
use function mkdir;

use PhpAiToolkit\Installer\RelativePathResolver;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function rmdir;
use function scandir;

use SplFileInfo;

use function sprintf;
use function symlink;
use function unlink;

/**
 * Installs skills from the package into the target project.
 *
 * Auto-detects AI agent directories in the project root and installs
 * skills to all detected agents. Supports symlink (default) and copy modes.
 */
final class InstallCommand
{
    /**
     * Known agent skill directory mappings.
     *
     * Each entry maps a parent directory marker to the skills subdirectory path.
     * When the marker directory exists in the project root, skills are installed
     * to the corresponding skills path.
     *
     * @var array<string, string>
     */
    private const AGENT_SKILL_DIRS = [
        '.claude' => '.claude/skills',
        '.agents' => '.agents/skills',
        '.continue' => '.continue/skills',
        '.openhands' => '.openhands/skills',
        '.windsurf' => '.windsurf/skills',
        '.factory' => '.factory/skills',
    ];

    /** @var Closure(string): void */
    private Closure $output;

    /**
     * @param Closure(string): void $output writer function for CLI output
     */
    public function __construct(
        private readonly string $projectRoot,
        private readonly string $packageRoot,
        Closure $output,
    ) {
        $this->output = $output;
    }

    /**
     * Executes the install command.
     *
     * Scans the package's skills directory and installs each skill
     * into detected agent skill directories. Falls back to .claude/skills/
     * if no agent directories are detected.
     *
     * @return int exit code (0 = success, 1 = error)
     */
    public function execute(bool $force = false, bool $copy = false): int
    {
        $skillsSource = $this->packageRoot . '/skills';
        if (!is_dir($skillsSource)) {
            $this->write('[INFO] No skills directory found in package.');

            return 0;
        }

        $skillDirs = $this->collectSkillDirs($skillsSource);
        if ($skillDirs === []) {
            $this->write('[INFO] No skills found in package.');

            return 0;
        }

        $stats = $this->installSkills($skillsSource, $skillDirs, $this->detectAgentDirs(), $force, $copy);
        $this->writeInstallSummary($stats);

        return $stats['errors'] > 0 ? 1 : 0;
    }

    /**
     * @return list<string>
     */
    private function collectSkillDirs(string $skillsSource): array
    {
        $scannedEntries = scandir($skillsSource);
        if ($scannedEntries === false) {
            return [];
        }

        $entries = array_diff($scannedEntries, ['.', '..', '.gitkeep']);
        $skillDirs = [];

        foreach ($entries as $entry) {
            if (is_dir($skillsSource . '/' . $entry)) {
                $skillDirs[] = $entry;
            }
        }

        return $skillDirs;
    }

    /**
     * @param list<string> $skillDirs
     * @param array<string, string> $targetDirs
     * @return array{installed: int, skipped: int, errors: int}
     */
    private function installSkills(
        string $skillsSource,
        array $skillDirs,
        array $targetDirs,
        bool $force,
        bool $copy,
    ): array {
        $stats = ['installed' => 0, 'skipped' => 0, 'errors' => 0];

        foreach ($targetDirs as $agentName => $skillsTarget) {
            $this->write(sprintf('  [%s]', $agentName));

            $absoluteTarget = $this->projectRoot . '/' . $skillsTarget;
            $this->ensureTargetDir($absoluteTarget);

            foreach ($skillDirs as $skillName) {
                $result = $this->installSkill($skillsSource, $absoluteTarget, $skillName, $force, $copy);
                $stats[$result]++;
            }
        }

        return $stats;
    }

    private function ensureTargetDir(string $absoluteTarget): void
    {
        if (!is_dir($absoluteTarget)) {
            mkdir($absoluteTarget, 0755, true);
        }
    }

    /**
     * @return 'installed'|'skipped'|'errors'
     */
    private function installSkill(
        string $skillsSource,
        string $absoluteTarget,
        string $skillName,
        bool $force,
        bool $copy,
    ): string {
        $targetPath = $absoluteTarget . '/' . $skillName;
        $sourcePath = $skillsSource . '/' . $skillName;

        if ((is_link($targetPath) || is_dir($targetPath)) && !$force) {
            $this->write(sprintf('    [SKIP] %s (already exists, use --force to overwrite)', $skillName));

            return 'skipped';
        }

        if (is_link($targetPath) || is_dir($targetPath)) {
            $this->removeTarget($targetPath);
        }

        return $copy
            ? $this->copySkill($sourcePath, $targetPath, $skillName)
            : $this->symlinkSkill($sourcePath, $targetPath, $absoluteTarget, $skillName);
    }

    /**
     * @return 'installed'|'errors'
     */
    private function copySkill(string $sourcePath, string $targetPath, string $skillName): string
    {
        if ($this->recursiveCopy($sourcePath, $targetPath)) {
            $this->write(sprintf('    [OK] %s (copied)', $skillName));

            return 'installed';
        }

        $this->write(sprintf('    [ERROR] %s (copy failed)', $skillName));

        return 'errors';
    }

    /**
     * @return 'installed'|'errors'
     */
    private function symlinkSkill(
        string $sourcePath,
        string $targetPath,
        string $absoluteTarget,
        string $skillName,
    ): string {
        $relativePath = RelativePathResolver::relativePath($absoluteTarget, $sourcePath);

        if (@symlink($relativePath, $targetPath)) {
            $this->write(sprintf('    [OK] %s -> %s', $skillName, $relativePath));

            return 'installed';
        }

        $this->write(sprintf('    [ERROR] %s (symlink failed, try --copy instead)', $skillName));

        return 'errors';
    }

    /**
     * @param array{installed: int, skipped: int, errors: int} $stats
     */
    private function writeInstallSummary(array $stats): void
    {
        $this->write(sprintf(
            'Done. %d skill(s) installed, %d skipped, %d error(s).',
            $stats['installed'],
            $stats['skipped'],
            $stats['errors'],
        ));
    }

    /**
     * Detects agent directories in the project root.
     *
     * Checks for known agent configuration directories and returns
     * the corresponding skills paths. Falls back to .claude/skills
     * if no agent directories are detected.
     *
     * @return array<string, string> map of agent name to skills directory path
     */
    private function detectAgentDirs(): array
    {
        $detected = [];

        foreach (self::AGENT_SKILL_DIRS as $marker => $skillsDir) {
            if (is_dir($this->projectRoot . '/' . $marker)) {
                $detected[$marker] = $skillsDir;
            }
        }

        if ($detected === []) {
            return ['.claude' => '.claude/skills'];
        }

        return $detected;
    }

    private function write(string $message): void
    {
        ($this->output)($message);
    }

    private function removeTarget(string $path): void
    {
        if (is_link($path)) {
            unlink($path);

            return;
        }

        if (is_dir($path)) {
            $this->recursiveRemove($path);
        }
    }

    private function recursiveRemove(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }

    private function recursiveCopy(string $source, string $dest): bool
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
}

<?php

declare(strict_types=1);

namespace PhpStanAiRules\Cli\Command;

use Closure;
use PhpStanAiRules\Cli\PathHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_diff;
use function copy;
use function is_dir;
use function is_link;
use function mkdir;
use function rmdir;
use function scandir;
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

        $entries = array_diff(scandir($skillsSource), ['.', '..', '.gitkeep']);
        $skillDirs = [];

        foreach ($entries as $entry) {
            if (is_dir($skillsSource . '/' . $entry)) {
                $skillDirs[] = $entry;
            }
        }

        if ($skillDirs === []) {
            $this->write('[INFO] No skills found in package.');

            return 0;
        }

        $targetDirs = $this->detectAgentDirs();

        $totalInstalled = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($targetDirs as $agentName => $skillsTarget) {
            $this->write(sprintf('  [%s]', $agentName));

            $absoluteTarget = $this->projectRoot . '/' . $skillsTarget;

            if (!is_dir($absoluteTarget)) {
                mkdir($absoluteTarget, 0755, true);
            }

            foreach ($skillDirs as $skillName) {
                $targetPath = $absoluteTarget . '/' . $skillName;
                $sourcePath = $skillsSource . '/' . $skillName;

                if (is_link($targetPath) || is_dir($targetPath)) {
                    if (!$force) {
                        $this->write(sprintf('    [SKIP] %s (already exists, use --force to overwrite)', $skillName));
                        $totalSkipped++;

                        continue;
                    }

                    $this->removeTarget($targetPath);
                }

                if ($copy) {
                    if ($this->recursiveCopy($sourcePath, $targetPath)) {
                        $this->write(sprintf('    [OK] %s (copied)', $skillName));
                        $totalInstalled++;
                    } else {
                        $this->write(sprintf('    [ERROR] %s (copy failed)', $skillName));
                        $totalErrors++;
                    }
                } else {
                    $relativePath = PathHelper::relativePath($absoluteTarget, $sourcePath);

                    if (@symlink($relativePath, $targetPath)) {
                        $this->write(sprintf('    [OK] %s -> %s', $skillName, $relativePath));
                        $totalInstalled++;
                    } else {
                        $this->write(sprintf('    [ERROR] %s (symlink failed, try --copy instead)', $skillName));
                        $totalErrors++;
                    }
                }
            }
        }

        $this->write(sprintf(
            'Done. %d skill(s) installed, %d skipped, %d error(s).',
            $totalInstalled,
            $totalSkipped,
            $totalErrors,
        ));

        return $totalErrors > 0 ? 1 : 0;
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

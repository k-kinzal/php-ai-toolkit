<?php

declare(strict_types=1);

namespace PhpAiToolkit\Installer\Cli\Command;

use Closure;

use function is_dir;

/**
 * Installs skills from the package into the target project.
 *
 * Auto-detects AI agent directories in the project root and installs
 * skills to all detected agents. Supports symlink (default) and copy modes.
 */
final class InstallCommand
{
    /** @readonly */
    private SkillInstallationWriter $writer;

    /** @readonly */
    private PackageSkillDirectoryScanner $skillDirectoryScanner;

    /** @readonly */
    private AgentSkillDirectoryDetector $agentDirectoryDetector;

    /** @readonly */
    private SkillInstallationRunner $installationRunner;

    /**
     * @param Closure(string): void $output writer function for CLI output
     */
    public function __construct(
        /** @readonly */
        private string $projectRoot,
        /** @readonly */
        private string $packageRoot,
        Closure $output,
        ?PackageSkillDirectoryScanner $skillDirectoryScanner = null,
        ?AgentSkillDirectoryDetector $agentDirectoryDetector = null,
        ?SkillInstallationRunner $installationRunner = null,
    ) {
        $filesystemOperator = new SkillFilesystemOperator();
        $this->writer = new SkillInstallationWriter($output);
        $this->skillDirectoryScanner = $skillDirectoryScanner ?? new PackageSkillDirectoryScanner();
        $this->agentDirectoryDetector = $agentDirectoryDetector ?? new AgentSkillDirectoryDetector();
        $this->installationRunner = $installationRunner ?? new SkillInstallationRunner(
            $filesystemOperator,
            new SkillInstaller($filesystemOperator, $this->writer),
            $this->writer,
        );
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
            $this->writer->write('[INFO] No skills directory found in package.');

            return 0;
        }

        $skillDirs = $this->skillDirectoryScanner->scan($skillsSource);
        if ($skillDirs === []) {
            $this->writer->write('[INFO] No skills found in package.');

            return 0;
        }

        $stats = $this->installationRunner->install(
            $this->projectRoot,
            $skillsSource,
            $skillDirs,
            $this->agentDirectoryDetector->detect($this->projectRoot),
            $force,
            $copy,
        );
        $this->writer->summary($stats);

        return $stats['errors'] > 0 ? 1 : 0;
    }
}

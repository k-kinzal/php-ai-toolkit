<?php

declare(strict_types=1);

namespace PhpAiToolkit\Installer\Cli\Command;

use function array_diff;
use function is_dir;
use function scandir;

/**
 * Scans the package skill source directory for installable skill directories.
 */
final class PackageSkillDirectoryScanner
{
    /**
     * Returns skill directory names inside the package skills directory.
     *
     * @return list<string>
     */
    public function scan(string $skillsSource): array
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
}

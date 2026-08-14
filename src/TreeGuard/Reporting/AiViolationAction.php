<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Reporting;

use PhpAiToolkit\TreeGuard\Analysis\Violation;

/**
 * Selects remediation actions for individual TreeGuard violations.
 */
final class AiViolationAction
{
    /** @var array<string, string> */
    private const ACTIONS = [
        'max_files' => 'Group cohesive files into focused subdirectories or merge related files to reduce the direct file count.',
        'max_dirs' => 'Merge or flatten subdirectories to reduce the direct subdirectory count.',
        'max_total_files' => 'Split the subtree into smaller packages or restructure it until the total file count fits the limit.',
        'max_depth' => 'Flatten the hierarchy by moving deeply nested contents closer to the matched directory.',
        'disallowed_file' => 'Rename, move, or delete the file so every direct file matches an allowed pattern.',
        'denied_file' => 'Rename, move, or delete the file so no direct file matches a denied pattern.',
        'disallowed_dir' => 'Rename, move, or delete the directory so every direct subdirectory matches an allowed pattern.',
        'denied_dir' => 'Rename, move, or delete the directory so no direct subdirectory matches a denied pattern.',
        'missing_required_file' => 'Create the required file with its intended content.',
        'empty_directory' => 'Delete the empty directory or add its intended contents.',
        'file_case' => 'Rename the file to follow the configured naming convention.',
        'dir_case' => 'Rename the directory to follow the configured naming convention.',
    ];

    /**
     * Returns an action message for the violation rule.
     */
    public function action(Violation $violation): string
    {
        return self::ACTIONS[$violation->rule] ?? 'Restructure the directory tree to satisfy the configured constraint.';
    }
}

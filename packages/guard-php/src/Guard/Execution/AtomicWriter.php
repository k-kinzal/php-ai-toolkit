<?php

declare(strict_types=1);

namespace Toolkit\Guard\Execution;

use Toolkit\Guard\Policy\PolicyException;

/**
 * Commits validated files with same-directory atomic replacements.
 */
final class AtomicWriter
{
    /**
     * @param list<FileChange> $changes
     * @throws PolicyException when a file changed after planning
     */
    public function apply(array $changes): void
    {
        foreach ($changes as $change) {
            $this->verify($change);
        }
        foreach ($changes as $change) {
            $this->write($change);
        }
    }
    /**
     * @throws PolicyException when the target changed or became a symlink
     */
    public function verify(FileChange $change): void
    {
        if (is_link($change->path) || file_get_contents($change->path) !== $change->original) {
            throw new PolicyException('Target changed during planning: ' . $change->path . '. Run guard again.');
        }
    }
    /**
     * @throws PolicyException when an atomic write fails
     */
    public function write(FileChange $change): void
    {
        $this->verify($change);
        $temporary = tempnam(dirname($change->path), '.guard-');
        if ($temporary === false) {
            throw new PolicyException('Cannot create a temporary file beside ' . $change->path . '. Check directory permissions.');
        }
        try {
            $permissions = fileperms($change->path);
            if (file_put_contents($temporary, $change->replacement) !== strlen($change->replacement)
                || $permissions === false || !chmod($temporary, $permissions & 0777)) {
                throw new PolicyException('Cannot stage ' . $change->path . '. Check available space and permissions.');
            }
            $this->verify($change);
            if (!rename($temporary, $change->path)) {
                throw new PolicyException('Cannot replace ' . $change->path . '. Check directory permissions.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
}

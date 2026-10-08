<?php

declare(strict_types=1);

namespace Guard\Collect\Filesystem;

use Guard\Diagnostic\PolicyException;

/**
 * Confines configuration edits to regular files inside the project root.
 */
final class TargetPath
{
    private Snapshot $snapshot;
    /**
     * Creates the TargetPath with its declared dependencies.
     */
    public function __construct(?Snapshot $snapshot = null)
    {
        $this->snapshot = $snapshot ?? new Snapshot(new NativeFilesystem());
    }
    /**
     * @throws PolicyException when a path escapes the root, traverses symlinks, or is not an existing regular file
     */
    public function resolve(string $root, string $relative): string
    {
        $path = $this->confine($root, $relative);
        if (!$this->snapshot->inspect($path)->file) {
            throw new PolicyException('Target "' . $relative . '" does not exist. Create the configuration file first.');
        }
        return $path;
    }
    /**
     * Returns the absolute path inside the root without requiring it to exist, so a policy can report a missing file.
     *
     * @throws PolicyException when a path escapes the root or traverses symlinks
     */
    public function confine(string $root, string $relative): string
    {
        $parts = explode('/', str_replace('\\', '/', $relative));
        if (str_starts_with($relative, '/') || preg_match('/^[A-Za-z]:/', $relative) === 1 || in_array('..', $parts, true)) {
            throw new PolicyException('Target "' . $relative . '" must stay inside the directory containing guard.yaml.');
        }
        $path = rtrim($root, '/');
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            $path .= '/' . $part;
            if ($this->snapshot->inspect($path)->link) {
                throw new PolicyException('Target "' . $relative . '" traverses a symlink. Select a regular project file.');
            }
        }
        return $path;
    }
}

<?php

declare(strict_types=1);

namespace Guard\Collect\Filesystem;

use Guard\Collect\Matching\ScopeMatcher;
use Guard\Collect\Matching\SelectionFilter;
use Guard\Diagnostic\PolicyException;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\PathPatternMatcher;
use Guard\Input\Selection;

/**
 * Collects index entries, including tracked files removed from the working tree.
 */
final class GitTrackedFiles
{
    /**
     * Queries Git without changing the index or following tracked symlinks.
     * @throws PolicyException when Git cannot read the repository index
     */
    public function collect(string $root, Selection $selection, QueryResult $result, ?ScopeMatcher $scope): void
    {
        if ($selection->paths === []) {
            return;
        }
        foreach ($this->paths($root) as $path) {
            if (($scope !== null && !$scope->contains($path)) || !(new SelectionFilter())->includes($selection, $path)) {
                continue;
            }
            foreach ($selection->paths as $pattern) {
                if ((new PathPatternMatcher())->matches($pattern, $path)) {
                    $absolute = rtrim($root, '/') . '/' . $path;
                    $result->addFile(new FileRecord($absolute, $path, new Entry(false, false, false, $absolute)));
                    break;
                }
            }
        }
    }

    /**
     * Lists paths relative to the selected project, also inside monorepos and worktrees.
     * @return list<string>
     * @throws PolicyException when Git is unavailable or the root is not a working tree
     */
    public function paths(string $root): array
    {
        $process = proc_open(['git', '-C', $root, 'ls-files', '--cached', '-z', '--'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if ($process === false) {
            throw new PolicyException('Cannot start git ls-files. Install Git to check generated artifact tracking.');
        }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0 || $output === false) {
            throw new PolicyException('Cannot read Git index for ' . $root . ': ' . trim($error === false ? '' : $error) . '. Run this policy inside a Git working tree with Git installed.');
        }
        return array_values(array_unique(array_filter(explode("\0", $output), static fn (string $path): bool => $path !== '')));
    }
}

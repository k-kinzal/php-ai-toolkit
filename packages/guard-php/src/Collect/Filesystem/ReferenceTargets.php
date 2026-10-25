<?php

declare(strict_types=1);

namespace Guard\Collect\Filesystem;

use Guard\Collect\Matching\ScopeMatcher;
use Guard\Input\FileRecord;
use Guard\Input\Path;
use Guard\Input\Scope;
use Guard\Structure\LocalReferences;

/**
 * Resolves referenced paths through the same snapshot and collection boundary.
 */
final class ReferenceTargets
{
    /**
     * Shares cached metadata without reading linked file contents.
     */
    public function __construct(private Snapshot $snapshot)
    {
    }

    /**
     * Omits targets outside the root or scope and paths traversing symlinks.
     * @return array<string, FileRecord>
     */
    public function resolve(string $root, FileRecord $source, LocalReferences $references, ?Scope $scope): array
    {
        $paths = new Path();
        $boundary = new ScopeMatcher($root, $this->snapshot->inspect($root)->identity, $scope ?? new Scope());
        $result = [];
        foreach ($references->paths() as $reference) {
            $relative = $paths->normalize(dirname($source->relativePath) . '/' . $reference);
            if (!$boundary->contains($relative)) {
                continue;
            }
            $entry = (new ScopedRoots($this->snapshot, $boundary))->entry($root, $relative === '.' ? '' : $relative);
            if ($entry !== null && $boundary->accepts($relative, $entry)) {
                $result[$reference] = new FileRecord($paths->absolute($root, $relative), $relative, $entry);
            }
        }
        return $result;
    }
}

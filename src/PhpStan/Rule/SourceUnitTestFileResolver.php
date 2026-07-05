<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Resolves the expected unit test file path for a source file.
 */
final class SourceUnitTestFileResolver
{
    /** @readonly */
    private PathMarkerSplitter $pathSplitter;

    /** @readonly */
    private SrcUnitTestRelativePathMapper $pathMapper;

    /**
     * Creates a resolver from src and unit test path markers.
     */
    public function __construct(
        /** @readonly */
        private string $srcMarker = '/src/',
        /** @readonly */
        private string $unitTestMarker = '/tests/Unit/',
        ?PathMarkerSplitter $pathSplitter = null,
        ?SrcUnitTestRelativePathMapper $pathMapper = null,
    ) {
        $this->pathSplitter = $pathSplitter ?? new PathMarkerSplitter();
        $this->pathMapper = $pathMapper ?? new SrcUnitTestRelativePathMapper();
    }

    /**
     * Returns the expected unit test file path for the source file.
     */
    public function resolve(string $sourceFile): ?string
    {
        $split = $this->pathSplitter->split($sourceFile, $this->srcMarker);
        if ($split === null) {
            return null;
        }

        [$root, $relativePath] = $split;

        return $root . $this->unitTestMarker . $this->pathMapper->toUnitTestRelativePath($relativePath);
    }
}

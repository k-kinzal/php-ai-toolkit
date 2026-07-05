<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;

/**
 * Inspects a source file for its expected unit test pair.
 */
final class SourceFileUnitTestPairInspector
{
    private readonly SrcUnitTestRelativePathMapper $pathMapper;

    private readonly FilenameExclusionMatcher $exclusionMatcher;

    private readonly SrcUnitTestPairErrorBuilder $errorBuilder;

    /**
     * Creates an inspector for source-file-to-unit-test pairing.
     *
     * @param list<string> $excludePatterns filename exclusion patterns
     */
    public function __construct(
        private readonly string $srcMarker,
        private readonly string $unitTestMarker,
        array $excludePatterns = [],
        ?SrcUnitTestRelativePathMapper $pathMapper = null,
        ?FilenameExclusionMatcher $exclusionMatcher = null,
        ?SrcUnitTestPairErrorBuilder $errorBuilder = null,
    ) {
        $this->pathMapper = $pathMapper ?? new SrcUnitTestRelativePathMapper();
        $this->exclusionMatcher = $exclusionMatcher ?? new FilenameExclusionMatcher($excludePatterns);
        $this->errorBuilder = $errorBuilder ?? new SrcUnitTestPairErrorBuilder();
    }

    /**
     * @param array{string, string} $srcSplit
     * @return list<IdentifierRuleError>
     */
    public function errors(string $file, array $srcSplit): array
    {
        [$packageRoot, $srcRelativePath] = $srcSplit;

        if ($this->exclusionMatcher->matches(basename($file))) {
            return [];
        }

        $expectedTestRelativePath = $this->pathMapper->toUnitTestRelativePath($srcRelativePath);
        $expectedTestPath = $packageRoot . $this->unitTestMarker . $expectedTestRelativePath;

        if (is_file($expectedTestPath)) {
            return [];
        }

        return [$this->errorBuilder->missingUnitTest(
            $this->srcMarker,
            $this->unitTestMarker,
            $srcRelativePath,
            $expectedTestRelativePath,
        )];
    }
}

<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;

/**
 * Inspects a unit test file for its expected source file pair.
 */
final class UnitTestSourcePairInspector
{
    private readonly SrcUnitTestRelativePathMapper $pathMapper;

    private readonly SrcUnitTestPairErrorBuilder $errorBuilder;

    /**
     * Creates an inspector for unit-test-to-source pairing.
     */
    public function __construct(
        private readonly string $srcMarker,
        private readonly string $unitTestMarker,
        ?SrcUnitTestRelativePathMapper $pathMapper = null,
        ?SrcUnitTestPairErrorBuilder $errorBuilder = null,
    ) {
        $this->pathMapper = $pathMapper ?? new SrcUnitTestRelativePathMapper();
        $this->errorBuilder = $errorBuilder ?? new SrcUnitTestPairErrorBuilder();
    }

    /**
     * @param array{string, string} $testSplit
     * @return list<IdentifierRuleError>
     */
    public function errors(array $testSplit): array
    {
        [$packageRoot, $testRelativePath] = $testSplit;
        $expectedSourceRelativePath = $this->pathMapper->toSourceRelativePath($testRelativePath);
        $expectedSourcePath = $packageRoot . $this->srcMarker . $expectedSourceRelativePath;

        if (is_file($expectedSourcePath)) {
            return [];
        }

        return [$this->errorBuilder->missingSource(
            $this->srcMarker,
            $this->unitTestMarker,
            $testRelativePath,
            $expectedSourceRelativePath,
        )];
    }
}

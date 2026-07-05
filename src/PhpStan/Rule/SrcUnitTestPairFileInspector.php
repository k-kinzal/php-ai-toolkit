<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;

/**
 * Inspects normalized files for src/tests unit pairing requirements.
 */
final class SrcUnitTestPairFileInspector
{
    private readonly RulePathNormalizer $pathNormalizer;

    private readonly PathMarkerSplitter $pathSplitter;

    private readonly SourceFileUnitTestPairInspector $sourceInspector;

    private readonly UnitTestSourcePairInspector $unitTestInspector;

    /**
     * Creates a file inspector from path markers and pairing inspectors.
     *
     * @param list<string> $excludePatterns filename exclusion patterns
     */
    public function __construct(
        private readonly string $srcMarker = '/src/',
        private readonly string $unitTestMarker = '/tests/Unit/',
        array $excludePatterns = [],
        ?RulePathNormalizer $pathNormalizer = null,
        ?PathMarkerSplitter $pathSplitter = null,
        ?SourceFileUnitTestPairInspector $sourceInspector = null,
        ?UnitTestSourcePairInspector $unitTestInspector = null,
    ) {
        $this->pathNormalizer = $pathNormalizer ?? new RulePathNormalizer();
        $this->pathSplitter = $pathSplitter ?? new PathMarkerSplitter();
        $this->sourceInspector = $sourceInspector ?? new SourceFileUnitTestPairInspector($srcMarker, $unitTestMarker, $excludePatterns);
        $this->unitTestInspector = $unitTestInspector ?? new UnitTestSourcePairInspector($srcMarker, $unitTestMarker);
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function errors(string $file): array
    {
        $normalizedFile = $this->pathNormalizer->normalize($file);
        if (!str_ends_with($normalizedFile, '.php')) {
            return [];
        }

        $srcSplit = $this->pathSplitter->split($normalizedFile, $this->srcMarker);
        if ($srcSplit !== null) {
            return $this->sourceInspector->errors($normalizedFile, $srcSplit);
        }

        $testSplit = $this->pathSplitter->split($normalizedFile, $this->unitTestMarker);
        if ($testSplit !== null) {
            return $this->unitTestInspector->errors($testSplit);
        }

        return [];
    }
}

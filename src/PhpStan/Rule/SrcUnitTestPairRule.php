<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<FileNode>
 */
final class SrcUnitTestPairRule implements Rule
{
    /** @readonly */
    private SrcUnitTestPairFileInspector $fileInspector;

    /**
     * @param list<string> $excludePatterns glob patterns for filenames to exclude
     * @param string $srcMarker path marker identifying source directories
     * @param string $unitTestMarker path marker identifying unit test directories
     */
    public function __construct(
        array $excludePatterns = [],
        string $srcMarker = '/src/',
        string $unitTestMarker = '/tests/Unit/',
        ?SrcUnitTestPairFileInspector $fileInspector = null,
    ) {
        $this->fileInspector = $fileInspector ?? new SrcUnitTestPairFileInspector($srcMarker, $unitTestMarker, $excludePatterns);
    }

    /**
     * @return class-string<FileNode>
     */
    public function getNodeType(): string
    {
        return FileNode::class;
    }

    /**
     * @param FileNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        unset($node);

        return $this->fileInspector->errors($scope->getFile());
    }
}

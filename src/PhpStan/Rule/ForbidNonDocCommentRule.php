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
final class ForbidNonDocCommentRule implements Rule
{
    /** @readonly */
    private FileTokenParser $fileTokenParser;

    /** @readonly */
    private NonDocCommentTokenAnalyzer $tokenAnalyzer;

    /**
     * Creates the rule from tokenizer parsing and token analysis.
     */
    public function __construct(?FileTokenParser $fileTokenParser = null, ?NonDocCommentTokenAnalyzer $tokenAnalyzer = null)
    {
        $this->fileTokenParser = $fileTokenParser ?? new FileTokenParser();
        $this->tokenAnalyzer = $tokenAnalyzer ?? new NonDocCommentTokenAnalyzer();
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

        $tokens = $this->fileTokenParser->parse($scope->getFile());
        if ($tokens === null) {
            return [];
        }

        return $this->tokenAnalyzer->errors($tokens);
    }
}

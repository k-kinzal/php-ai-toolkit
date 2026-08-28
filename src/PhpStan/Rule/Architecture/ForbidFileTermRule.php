<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Architecture;

use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * Forbids configured terms in files belonging to restricted paths.
 *
 * @implements Rule<FileNode>
 */
final class ForbidFileTermRule implements Rule
{
    /** @readonly */
    private ForbiddenFileTermInspector $inspector;

    /**
     * Creates the rule from path-specific forbidden terms.
     *
     * @param array<string, list<string>> $forbiddenTermsByPath path patterns mapped to literal forbidden terms
     */
    public function __construct(
        array $forbiddenTermsByPath = [],
        ?ForbiddenFileTermInspector $inspector = null,
    ) {
        $this->inspector = $inspector ?? new ForbiddenFileTermInspector(
            new ForbiddenFileTermRestrictions($forbiddenTermsByPath),
        );
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

        return $this->inspector->errors($scope->getFile());
    }
}

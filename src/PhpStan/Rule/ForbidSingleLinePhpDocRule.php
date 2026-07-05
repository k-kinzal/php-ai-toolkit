<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * Forbids single-line PHPDoc comments on public API elements.
 *
 * All PHPDoc comments on classes, interfaces, traits, enums,
 * public methods, public properties, and public constants
 * must use the multi-line format.
 *
 * @implements Rule<\PhpParser\Node\Stmt\ClassLike>
 */
final class ForbidSingleLinePhpDocRule implements Rule
{
    /** @readonly */
    private SingleLinePhpDocErrorCollector $errorCollector;

    /**
     * Creates the rule from single-line PHPDoc error collection.
     */
    public function __construct(?SingleLinePhpDocErrorCollector $errorCollector = null)
    {
        $this->errorCollector = $errorCollector ?? new SingleLinePhpDocErrorCollector();
    }

    /**
     * @return class-string<\PhpParser\Node\Stmt\ClassLike>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\ClassLike::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassLike $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        return $this->errorCollector->errors($node, $scope);
    }
}

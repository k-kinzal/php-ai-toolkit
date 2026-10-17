<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocErrorCollector;

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
     * @return class-string<ClassLike>
     */
    public function getNodeType(): string
    {
        return ClassLike::class;
    }

    /**
     * @param ClassLike $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        return $this->errorCollector->errors($node, $scope);
    }
}

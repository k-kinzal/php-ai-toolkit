<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;
use function str_contains;

use Toolkit\PhpStan\Rule\Shared\Path\RulePathMatcher;

/**
 * Forbids generic arrays with mixed values in callable return PHPDoc.
 *
 * A callable owns the values it returns, so its contract can state their
 * union, shape, or domain type instead of passing the uncertainty to every
 * caller. Parameter declarations remain unrestricted because an input
 * boundary can legitimately accept arbitrary values.
 *
 * @implements Rule<\PhpParser\Node\FunctionLike>
 */
final class ForbidMixedArrayReturnTypeRule implements Rule
{
    /** @readonly */
    private MixedArrayReturnTypeInspector $inspector;

    /** @readonly */
    private RulePathMatcher $pathMatcher;

    /**
     * Creates the rule from return declaration inspection.
     *
     * @param list<string> $mixedArrayReturnAllowedPaths fnmatch patterns of genuinely untyped return boundaries
     */
    public function __construct(
        array $mixedArrayReturnAllowedPaths = [],
        ?MixedArrayReturnTypeInspector $inspector = null,
        ?RulePathMatcher $pathMatcher = null,
    ) {
        $this->inspector = $inspector ?? new MixedArrayReturnTypeInspector();
        $this->pathMatcher = $pathMatcher ?? new RulePathMatcher($mixedArrayReturnAllowedPaths);
    }

    /**
     * @return class-string<FunctionLike>
     */
    public function getNodeType(): string
    {
        return FunctionLike::class;
    }

    /**
     * @param FunctionLike $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->pathMatcher->matches($scope->getFile())) {
            return [];
        }

        if (!$node instanceof ClassMethod && !$node instanceof Function_) {
            return [];
        }

        $docComment = $node->getDocComment();
        if ($docComment === null || !str_contains($docComment->getText(), 'return')) {
            return [];
        }

        $callableName = $this->callableName($node);
        $errors = [];
        foreach ($this->inspector->declarations($docComment->getText()) as $declaration) {
            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Replace "%s %s" on %s with an array value type that describes every returned value. Use a union, array shape, DTO, or domain object instead of mixed.',
                    $declaration['tag'],
                    $declaration['type'],
                    $callableName
                )
            )
                ->identifier('customRules.mixedArrayReturnType')
                ->line($node->getStartLine())
                ->build();
        }

        return $errors;
    }

    /**
     * Names one callable precisely enough to fix its return declaration.
     */
    public function callableName(ClassMethod|Function_ $node): string
    {
        return sprintf('%s()', $node->name->toString());
    }
}

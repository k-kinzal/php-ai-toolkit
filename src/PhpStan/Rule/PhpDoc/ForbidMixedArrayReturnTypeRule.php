<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\PhpDoc;

use PhpAiToolkit\PhpStan\Rule\Shared\RulePathMatcher;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;
use function str_contains;

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
     * @return class-string<\PhpParser\Node\FunctionLike>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\FunctionLike::class;
    }

    /**
     * @param \PhpParser\Node\FunctionLike $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if ($this->pathMatcher->matches($scope->getFile())) {
            return [];
        }

        if (!$node instanceof \PhpParser\Node\Stmt\ClassMethod && !$node instanceof \PhpParser\Node\Stmt\Function_) {
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
    public function callableName(\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_ $node): string
    {
        return sprintf('%s()', $node->name->toString());
    }
}

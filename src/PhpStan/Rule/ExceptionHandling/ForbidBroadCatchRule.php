<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\ExceptionHandling;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

use function sprintf;
use function strtolower;

/**
 * Forbids catching Throwable, Exception, and the LogicException and Error
 * families outside a configured boundary layer.
 *
 * Broad catches swallow programmer errors together with the failures they
 * meant to handle. Only designated boundary files (entry points, middleware,
 * worker loops) may catch these types.
 *
 * @implements Rule<\PhpParser\Node\Stmt\Catch_>
 */
final class ForbidBroadCatchRule implements Rule
{
    /** @readonly */
    private BroadCatchPathMatcher $pathMatcher;

    /**
     * @param list<string> $broadCatchAllowedPaths fnmatch patterns of boundary files allowed to catch broadly
     */
    public function __construct(
        array $broadCatchAllowedPaths = [],
        ?BroadCatchPathMatcher $pathMatcher = null,
    ) {
        $this->pathMatcher = $pathMatcher ?? new BroadCatchPathMatcher($broadCatchAllowedPaths);
    }

    /**
     * @return class-string<\PhpParser\Node\Stmt\Catch_>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\Catch_::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\Catch_ $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if ($this->pathMatcher->isAllowed($scope->getFile())) {
            return [];
        }

        $errors = [];
        foreach ($node->types as $typeName) {
            $writtenName = $typeName->toString();
            $className = $scope->resolveName($typeName);
            $lowerClassName = strtolower($className);

            if ($lowerClassName === 'throwable' || $lowerClassName === 'exception') {
                $reason = sprintf('catch (%s) intercepts every failure, including programmer errors', $writtenName);
            } elseif ((new ObjectType('LogicException'))->isSuperTypeOf(new ObjectType($className))->yes()) {
                $reason = sprintf('%s is a programmer error (LogicException family) that must be fixed at its source, not caught', $writtenName);
            } elseif ((new ObjectType('Error'))->isSuperTypeOf(new ObjectType($className))->yes()) {
                $reason = sprintf('%s is an engine failure (Error family) that must be fixed at its source, not caught', $writtenName);
            } else {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Catch a specific exception type instead of "%s": %s. If this catch is an intentional top-level boundary handler, add its file path to customRules.broadCatchAllowedPaths.',
                    $writtenName,
                    $reason
                )
            )
                ->identifier('customRules.broadCatch')
                ->line($node->getStartLine())
                ->build();
        }

        return $errors;
    }
}

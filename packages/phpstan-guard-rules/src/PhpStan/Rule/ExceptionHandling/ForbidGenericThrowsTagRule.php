<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ExceptionHandling;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;
use function str_contains;
use function strtolower;

/**
 * Forbids @throws tags that declare the generic \Exception or \Throwable.
 *
 * A generic @throws tag carries no information: callers cannot decide what
 * to catch, and checked-exception analysis degenerates into "may throw
 * anything". The tag must name the concrete exception types.
 *
 * @implements Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
final class ForbidGenericThrowsTagRule implements Rule
{
    /**
     * @return class-string<\PhpParser\Node\Stmt\ClassMethod>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\ClassMethod::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        $docComment = $node->getDocComment();
        if ($docComment === null || !str_contains($docComment->getText(), '@throws')) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        $methodName = $node->name->toString();
        if (!$classReflection->hasNativeMethod($methodName)) {
            return [];
        }

        $throwType = $classReflection->getNativeMethod($methodName)->getThrowType();
        if ($throwType === null) {
            return [];
        }

        $errors = [];
        foreach ($throwType->getObjectClassNames() as $className) {
            $lowerClassName = strtolower($className);
            if ($lowerClassName !== 'exception' && $lowerClassName !== 'throwable') {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Replace "@throws \%s" on %s() with the concrete exception types the method can raise. A generic @throws tag gives callers nothing to catch selectively and defeats checked-exception analysis.',
                    $className,
                    $methodName
                )
            )
                ->identifier('customRules.genericThrowsTag')
                ->line($node->getStartLine())
                ->build();
        }

        return $errors;
    }
}

<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Collects missing PHPDoc errors for public API methods.
 */
final class PublicApiMethodPhpDocErrorCollector
{
    /**
     * @return list<IdentifierRuleError>
     */
    public function errors(\PhpParser\Node\Stmt\ClassLike $node, string $className): array
    {
        $errors = [];
        foreach ($node->getMethods() as $method) {
            if (!$method->isPublic()) {
                continue;
            }

            if ($method->getDocComment() === null) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Public method %s::%s() is missing a PHPDoc comment. Add a multi-line /** ... */ block describing what this method does, its parameters, and return value.',
                        $className,
                        $method->name->toString()
                    )
                )
                    ->identifier('customRules.requirePhpDocOnMethod')
                    ->line($method->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }
}

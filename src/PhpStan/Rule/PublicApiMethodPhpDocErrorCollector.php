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
                        'Add a multi-line PHPDoc block to public method %s::%s() describing behavior, parameters, and return value.',
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

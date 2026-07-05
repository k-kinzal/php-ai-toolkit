<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Collects missing PHPDoc errors for public API class-like declarations.
 */
final class PublicApiClassPhpDocErrorCollector
{
    /**
     * @return list<IdentifierRuleError>
     */
    public function errors(
        \PhpParser\Node\Stmt\ClassLike $node,
        string $kindLabel,
        string $className,
    ): array {
        if ($node->getDocComment() !== null) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Add a multi-line PHPDoc block to %s %s describing its purpose.',
                    $kindLabel,
                    $className
                )
            )
                ->identifier('customRules.requirePhpDocOnClass')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}

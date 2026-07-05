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
                    '%s %s is missing a PHPDoc comment. Add a multi-line /** ... */ block describing its purpose.',
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

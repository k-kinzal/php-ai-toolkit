<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Collects missing PHPDoc errors for public API constants.
 */
final class PublicApiConstantPhpDocErrorCollector
{
    /**
     * @return list<IdentifierRuleError>
     */
    public function errors(\PhpParser\Node\Stmt\ClassLike $node, string $className): array
    {
        $errors = [];
        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof \PhpParser\Node\Stmt\ClassConst) {
                continue;
            }

            if (!$stmt->isPublic()) {
                continue;
            }

            $names = array_map(
                static fn (\PhpParser\Node\Const_ $const): string => $const->name->toString(),
                $stmt->consts,
            );

            if ($stmt->getDocComment() === null) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Public constant %s::%s is missing a PHPDoc comment. Add a multi-line /** ... */ block describing this constant.',
                        $className,
                        implode(', ', $names)
                    )
                )
                    ->identifier('customRules.requirePhpDocOnConstant')
                    ->line($stmt->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }
}

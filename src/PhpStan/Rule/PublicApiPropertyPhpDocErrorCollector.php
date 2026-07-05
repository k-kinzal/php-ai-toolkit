<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Collects missing PHPDoc errors for public API properties.
 */
final class PublicApiPropertyPhpDocErrorCollector
{
    /**
     * @return list<IdentifierRuleError>
     */
    public function errors(\PhpParser\Node\Stmt\ClassLike $node, string $className): array
    {
        $errors = [];
        foreach ($node->getProperties() as $property) {
            if (!$property->isPublic()) {
                continue;
            }

            $names = array_map(
                static fn (\PhpParser\Node\PropertyItem $prop): string => '$' . $prop->name->toString(),
                $property->props,
            );

            if ($property->getDocComment() === null) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Public property %s::%s is missing a PHPDoc comment. Add a multi-line /** ... */ block describing this property.',
                        $className,
                        implode(', ', $names)
                    )
                )
                    ->identifier('customRules.requirePhpDocOnProperty')
                    ->line($property->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }
}

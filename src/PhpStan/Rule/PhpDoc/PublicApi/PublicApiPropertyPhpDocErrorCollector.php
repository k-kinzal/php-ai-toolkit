<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\PhpDoc\PublicApi;

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

            $names = [];
            foreach ($property->props as $declared) {
                $names[] = '$' . $declared->name->toString();
            }

            if ($property->getDocComment() === null) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Add a multi-line PHPDoc block to public property %s::%s describing the property.',
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

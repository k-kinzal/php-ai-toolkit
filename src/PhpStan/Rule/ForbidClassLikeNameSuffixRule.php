<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids configured suffixes on class-like declaration names.
 *
 * @implements Rule<\PhpParser\Node\Stmt\ClassLike>
 */
final class ForbidClassLikeNameSuffixRule implements Rule
{
    /** @readonly */
    private ForbiddenClassLikeSuffixes $forbiddenSuffixes;

    /** @readonly */
    private ClassLikeKindLabel $kindLabel;

    /**
     * @param list<string> $forbiddenSuffixes class-like name suffixes to forbid
     */
    public function __construct(array $forbiddenSuffixes = [])
    {
        $this->forbiddenSuffixes = new ForbiddenClassLikeSuffixes($forbiddenSuffixes);
        $this->kindLabel = new ClassLikeKindLabel();
    }

    /**
     * @return class-string<\PhpParser\Node\Stmt\ClassLike>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\ClassLike::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassLike $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if ($node->name === null) {
            return [];
        }

        $name = $node->name->toString();
        $suffix = $this->forbiddenSuffixes->matchingSuffix($name);

        if ($suffix === null) {
            return [];
        }

        $kind = $this->kindLabel->label($node);

        return [
            RuleErrorBuilder::message(
                sprintf(
                    '%s %s uses forbidden suffix "%s". Rename this %s so its name does not end with "%s"; use a specific domain name instead.',
                    ucfirst($kind),
                    $name,
                    $suffix,
                    $kind,
                    $suffix
                )
            )
                ->identifier('customRules.forbiddenClassLikeNameSuffix')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}

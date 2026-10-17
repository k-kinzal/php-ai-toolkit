<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Naming;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Toolkit\PhpStan\Rule\Shared\ClassLikeKindLabel;

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
     * @return class-string<ClassLike>
     */
    public function getNodeType(): string
    {
        return ClassLike::class;
    }

    /**
     * @param ClassLike $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
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
                    'Rename %s %s to a specific domain name without the "%s" suffix.',
                    $kind,
                    $name,
                    $suffix
                )
            )
                ->identifier('customRules.forbiddenClassLikeNameSuffix')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}

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
    /** @var list<string> */
    private array $forbiddenSuffixes;

    /**
     * @param list<string> $forbiddenSuffixes class-like name suffixes to forbid
     */
    public function __construct(array $forbiddenSuffixes = [])
    {
        $this->forbiddenSuffixes = array_values(array_unique(array_filter(
            $forbiddenSuffixes,
            static fn (string $suffix): bool => $suffix !== '',
        )));
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
        if ($this->forbiddenSuffixes === []) {
            return [];
        }

        if ($node->name === null) {
            return [];
        }

        $name = $node->name->toString();

        foreach ($this->forbiddenSuffixes as $suffix) {
            if (str_ends_with($name, $suffix)) {
                return [$this->buildError($node, $name, $suffix)];
            }
        }

        return [];
    }

    private function buildError(
        \PhpParser\Node\Stmt\ClassLike $node,
        string $name,
        string $suffix,
    ): IdentifierRuleError {
        $kind = $this->resolveKindLabel($node);

        return RuleErrorBuilder::message(
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
            ->build();
    }

    private function resolveKindLabel(\PhpParser\Node\Stmt\ClassLike $node): string
    {
        if ($node instanceof \PhpParser\Node\Stmt\Interface_) {
            return 'interface';
        }

        if ($node instanceof \PhpParser\Node\Stmt\Trait_) {
            return 'trait';
        }

        if ($node instanceof \PhpParser\Node\Stmt\Enum_) {
            return 'enum';
        }

        return 'class';
    }
}

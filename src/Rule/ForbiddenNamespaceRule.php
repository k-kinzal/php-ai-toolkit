<?php

declare(strict_types=1);

namespace PhpStanAiRules\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids configured namespace prefixes.
 *
 * @implements Rule<\PhpParser\Node\Stmt\Namespace_>
 */
final class ForbiddenNamespaceRule implements Rule
{
    /** @var list<string> */
    private array $forbiddenNamespacePrefixes;

    /**
     * @param list<string> $forbiddenNamespacePrefixes namespace prefixes to forbid
     */
    public function __construct(array $forbiddenNamespacePrefixes = [])
    {
        $this->forbiddenNamespacePrefixes = array_values(array_unique(array_filter(
            array_map([$this, 'normalizeNamespacePrefix'], $forbiddenNamespacePrefixes),
            static fn (string $prefix): bool => $prefix !== '',
        )));
    }

    /**
     * @return class-string<\PhpParser\Node\Stmt\Namespace_>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\Namespace_::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\Namespace_ $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if ($this->forbiddenNamespacePrefixes === []) {
            return [];
        }

        if ($node->name === null) {
            return [];
        }

        $namespace = $node->name->toString();

        foreach ($this->forbiddenNamespacePrefixes as $prefix) {
            if ($this->matchesForbiddenPrefix($namespace, $prefix)) {
                return [$this->buildError($node, $namespace, $prefix)];
            }
        }

        return [];
    }

    private function normalizeNamespacePrefix(string $prefix): string
    {
        return trim(str_replace('/', '\\', $prefix), '\\');
    }

    private function matchesForbiddenPrefix(string $namespace, string $prefix): bool
    {
        return $namespace === $prefix || str_starts_with($namespace, $prefix . '\\');
    }

    private function buildError(
        \PhpParser\Node\Stmt\Namespace_ $node,
        string $namespace,
        string $prefix,
    ): IdentifierRuleError {
        return RuleErrorBuilder::message(
            sprintf(
                'Namespace "%s" is prohibited by forbidden prefix "%s". Do not create generic test support/helper/utility namespaces; use an existing library, create an independent internal library outside the Tests namespace, or accept duplication and write setup directly inside each test method.',
                $namespace,
                $prefix
            )
        )
            ->identifier('customRules.forbiddenNamespace')
            ->line($node->getStartLine())
            ->build();
    }
}

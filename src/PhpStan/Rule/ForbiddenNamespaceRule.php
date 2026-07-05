<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

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
    /** @readonly */
    private ForbiddenNamespacePrefixes $forbiddenNamespacePrefixes;

    /**
     * @param list<string> $forbiddenNamespacePrefixes namespace prefixes to forbid
     */
    public function __construct(array $forbiddenNamespacePrefixes = [])
    {
        $this->forbiddenNamespacePrefixes = new ForbiddenNamespacePrefixes($forbiddenNamespacePrefixes);
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
        if ($node->name === null) {
            return [];
        }

        $namespace = $node->name->toString();
        $prefix = $this->forbiddenNamespacePrefixes->matchingPrefix($namespace);

        if ($prefix === null) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Namespace "%s" is prohibited by forbidden prefix "%s". Do not create generic test support/helper/utility namespaces; use an existing library, create an independent internal library outside the Tests namespace, or accept duplication and write setup directly inside each test method.',
                    $namespace,
                    $prefix
                )
            )
                ->identifier('customRules.forbiddenNamespace')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}

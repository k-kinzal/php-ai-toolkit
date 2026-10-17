<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Naming;

use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids configured namespace prefixes and segments.
 *
 * @implements Rule<\PhpParser\Node\Stmt\Namespace_>
 */
final class ForbiddenNamespaceRule implements Rule
{
    /** @readonly */
    private ForbiddenNamespacePrefixes $forbiddenNamespacePrefixes;

    /** @var list<string> */
    private array $forbiddenNamespaceSegments;

    /**
     * @param list<string> $forbiddenNamespacePrefixes namespace prefixes to forbid
     * @param list<string> $forbiddenNamespaceSegments complete namespace segments to forbid at any depth
     */
    public function __construct(
        array $forbiddenNamespacePrefixes = [],
        array $forbiddenNamespaceSegments = [],
    ) {
        $this->forbiddenNamespacePrefixes = new ForbiddenNamespacePrefixes($forbiddenNamespacePrefixes);
        $this->forbiddenNamespaceSegments = $forbiddenNamespaceSegments;
    }

    /**
     * @return class-string<Namespace_>
     */
    public function getNodeType(): string
    {
        return Namespace_::class;
    }

    /**
     * @param Namespace_ $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name === null) {
            return [];
        }

        $namespace = $node->name->toString();
        $prefix = $this->forbiddenNamespacePrefixes->matchingPrefix($namespace);
        $segments = array_values(array_intersect(explode('\\', $namespace), $this->forbiddenNamespaceSegments));
        $segment = $segments[0] ?? null;

        if ($prefix === null && $segment === null) {
            return [];
        }

        $message = $prefix !== null
            ? sprintf(
                'Move code out of namespace "%s". Use a namespace outside forbidden test prefix "%s", or inline setup in each test.',
                $namespace,
                $prefix,
            )
            : sprintf(
                'Namespace "%s" contains forbidden segment "%s". Replace this segment with a domain-specific name and update namespace declarations and references.',
                $namespace,
                $segment,
            );

        return [
            RuleErrorBuilder::message($message)
                ->identifier('customRules.forbiddenNamespace')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}

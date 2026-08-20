<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\NamespaceVisibility;

use function count;
use function implode;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * Builds the errors reported for @visibility declarations and the usages that leave them.
 */
final class VisibilityErrorBuilder
{
    /** @readonly */
    private NamespaceLineage $lineage;

    /**
     * Creates the error building from namespace ancestry.
     */
    public function __construct(?NamespaceLineage $lineage = null)
    {
        $this->lineage = $lineage ?? new NamespaceLineage();
    }

    /**
     * Reports a usage that lies outside the scope its declaration allows.
     *
     * @param string $subject what the usage is, already phrased as a sentence subject
     */
    public function outOfScope(
        string $subject,
        VisibilityScope $scope,
        string $callerNamespace,
        string $declaringNamespace,
        int $line,
    ): IdentifierRuleError {
        return RuleErrorBuilder::message(
            sprintf(
                '%s is not visible from %s. The declaration is marked %s, so it may only be used from %s. Move the caller into that namespace, or widen the declaration to "@visibility %s".',
                $subject,
                $this->describeNamespace($callerNamespace),
                $scope->describeTags(),
                $this->describeAllowed($scope),
                $this->wideningFor($declaringNamespace, $callerNamespace)
            )
        )
            ->identifier('customRules.namespaceVisibility')
            ->line($line)
            ->build();
    }

    /**
     * Reports a @visibility tag that cannot be honoured as written.
     */
    public function tagProblem(string $subject, string $value, string $reason, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf('Fix "@visibility %s" on %s: %s.', $value, $subject, $reason)
        )
            ->identifier('customRules.namespaceVisibilityTag')
            ->line($line)
            ->build();
    }

    /**
     * Reports a declaration that combines "@visibility public" with a narrowing tag.
     */
    public function contradictoryTags(string $subject, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Remove either "@visibility public" or the narrowing @visibility tags on %s: "public" makes the declaration visible everywhere, so keeping both leaves the narrower tags with no effect.',
                $subject
            )
        )
            ->identifier('customRules.namespaceVisibilityTag')
            ->line($line)
            ->build();
    }

    /**
     * Phrases a namespace for a message, naming the global namespace explicitly.
     */
    public function describeNamespace(string $namespace): string
    {
        return $namespace === '' ? 'the global namespace' : sprintf('namespace "%s"', $namespace);
    }

    /**
     * Phrases the namespaces a scope allows.
     */
    public function describeAllowed(VisibilityScope $scope): string
    {
        $namespaces = $scope->allowedNamespaces();
        if (count($namespaces) === 1) {
            return sprintf('namespace "%s" and its sub-namespaces', $namespaces[0]);
        }

        $quoted = [];
        foreach ($namespaces as $namespace) {
            $quoted[] = sprintf('"%s"', $namespace);
        }

        return sprintf('namespaces %s and their sub-namespaces', implode(', ', $quoted));
    }

    /**
     * Returns the narrowest scope value that would let the caller through.
     */
    public function wideningFor(string $declaringNamespace, string $callerNamespace): string
    {
        $ancestor = $this->lineage->commonAncestorOf($declaringNamespace, $callerNamespace);

        return $ancestor === '' ? 'public' : $ancestor;
    }
}

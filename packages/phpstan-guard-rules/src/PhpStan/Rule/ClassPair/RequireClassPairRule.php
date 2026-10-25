<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ClassPair;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * Requires that a class matching one configured name template is paired with a class matching another.
 *
 * @implements Rule<InClassNode>
 */
final class RequireClassPairRule implements Rule
{
    /** @readonly */
    private ClassPairSet $pairs;

    /** @readonly */
    private ClassPairErrorBuilder $errorBuilder;

    /**
     * @param ReflectionProvider $reflectionProvider resolves whether the paired class exists
     * @param string $classPairProjectRoot directory holding the composer.json that resolves "${directory}" placeholders
     * @param list<array{from: string, to: string, exclude?: list<string>}> $classPairs pair definitions
     * @param list<string> $classPairExclude class name globs that no pair checks
     */
    public function __construct(
        /** @readonly */
        private ReflectionProvider $reflectionProvider,
        string $classPairProjectRoot,
        array $classPairs = ClassPairSet::DEFAULTS,
        array $classPairExclude = [],
    ) {
        $autoload = ComposerAutoloadMap::fromProjectRoot($classPairProjectRoot);
        $this->pairs = new ClassPairSet($autoload, $classPairs, $classPairExclude);
        $this->errorBuilder = new ClassPairErrorBuilder($autoload);
    }

    /**
     * @return class-string<InClassNode>
     */
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $reflection = $node->getClassReflection();
        if ($reflection->isAnonymous()) {
            return [];
        }

        $class = $reflection->getName();
        $line = $node->getOriginalNode()->getStartLine();
        $errors = [];
        foreach ($this->pairs->pairs() as $pair) {
            if ($pair->excludes($class)) {
                continue;
            }

            $counterpart = $pair->counterpartOf($class);
            if ($counterpart !== null && !$this->reflectionProvider->hasClass($counterpart)) {
                $errors[] = $this->errorBuilder->missing($class, $counterpart, $pair, $line);
            }

            $origin = $pair->originOf($class);
            if ($origin !== null && !$this->reflectionProvider->hasClass($origin)) {
                $errors[] = $this->errorBuilder->orphaned($class, $origin, $pair, $line);
            }
        }

        return $errors;
    }
}

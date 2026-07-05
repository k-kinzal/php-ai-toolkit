<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Decides which protected methods represent intentional extension points.
 */
final class ProtectedMethodPolicy
{
    /** @readonly */
    private OverrideAttributeDetector $overrideAttributeDetector;

    /**
     * Creates the policy from Override attribute detection.
     */
    public function __construct(?OverrideAttributeDetector $overrideAttributeDetector = null)
    {
        $this->overrideAttributeDetector = $overrideAttributeDetector ?? new OverrideAttributeDetector();
    }

    /**
     * Checks whether a protected method is allowed by the design rule.
     */
    public function allows(
        \PhpParser\Node\Stmt\ClassLike $classLike,
        \PhpParser\Node\Stmt\ClassMethod $method,
    ): bool {
        if ($classLike instanceof \PhpParser\Node\Stmt\Trait_) {
            return true;
        }

        if ($classLike instanceof \PhpParser\Node\Stmt\Class_ && $classLike->isAbstract()) {
            return true;
        }

        return $this->overrideAttributeDetector->has($method);
    }
}

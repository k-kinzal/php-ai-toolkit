<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ClassDesign;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Trait_;
use Toolkit\PhpStan\Rule\Shared\OverrideAttributeDetector;

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
        ClassLike $classLike,
        ClassMethod $method,
    ): bool {
        if ($classLike instanceof Trait_) {
            return true;
        }

        if ($classLike instanceof Class_ && $classLike->isAbstract()) {
            return true;
        }

        return $this->overrideAttributeDetector->has($method);
    }
}

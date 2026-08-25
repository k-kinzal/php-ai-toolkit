<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ClassDesign;

use PHPStan\Reflection\MethodReflection;

use function strtolower;

/**
 * Decides whether PHP would accept #[\Override] on a child method that shares
 * its name with the given parent method.
 *
 * A constructor never overrides the parent constructor, and a private parent
 * method is invisible to the child, so a child method of the same name declares
 * something new. PHP rejects the attribute in both cases with a fatal error
 * from 8.3 on, which is why neither can be reported: the fix the message asks
 * for would stop the code from running.
 */
final class OverridableMethodPolicy
{
    /**
     * Checks whether the attribute can legally be written on the child method.
     */
    public function allows(string $methodName, MethodReflection $parentMethod): bool
    {
        if (strtolower($methodName) === '__construct') {
            return false;
        }

        return !$parentMethod->isPrivate();
    }
}

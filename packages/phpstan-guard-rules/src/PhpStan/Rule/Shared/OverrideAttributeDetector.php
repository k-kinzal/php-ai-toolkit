<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Shared;

use PhpParser\Node\Stmt\ClassMethod;

/**
 * Detects the PHP Override attribute on class methods.
 */
final class OverrideAttributeDetector
{
    /**
     * Reports whether the method has #[Override] or #[\Override].
     */
    public function has(ClassMethod $node): bool
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $attr->name->toString();
                if ($name === 'Override' || $name === '\\Override') {
                    return true;
                }
            }
        }

        return false;
    }
}

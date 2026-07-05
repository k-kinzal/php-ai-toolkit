<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Detects the PHP Override attribute on class methods.
 */
final class OverrideAttributeDetector
{
    /**
     * Reports whether the method has #[Override] or #[\Override].
     */
    public function has(\PhpParser\Node\Stmt\ClassMethod $node): bool
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

<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Detects PHPUnit test methods by name or #[Test] attribute.
 */
final class TestMethodDetector
{
    /**
     * Reports whether the class method is a test method.
     */
    public function isTestMethod(\PhpParser\Node\Stmt\ClassMethod $node): bool
    {
        if (str_starts_with($node->name->toString(), 'test')) {
            return true;
        }

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attrName = $attr->name->toString();
                if ($attrName === 'Test' || str_ends_with($attrName, '\\Test')) {
                    return true;
                }
            }
        }

        return false;
    }
}

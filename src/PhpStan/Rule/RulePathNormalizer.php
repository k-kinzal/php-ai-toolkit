<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Normalizes filesystem paths for PHPStan path-based rules.
 */
final class RulePathNormalizer
{
    /**
     * Converts path separators to the rule-internal slash format.
     */
    public function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}

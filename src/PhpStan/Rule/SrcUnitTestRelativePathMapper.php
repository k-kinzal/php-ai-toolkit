<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Maps source relative paths to unit test relative paths and back.
 */
final class SrcUnitTestRelativePathMapper
{
    /**
     * Returns the expected unit test relative path for a source file.
     */
    public function toUnitTestRelativePath(string $srcRelativePath): string
    {
        $basePath = substr($srcRelativePath, 0, -4);

        return $basePath . 'Test.php';
    }

    /**
     * Returns the expected source relative path for a unit test file.
     */
    public function toSourceRelativePath(string $testRelativePath): string
    {
        $withoutPhp = substr($testRelativePath, 0, -4);
        if (str_ends_with($withoutPhp, 'Test')) {
            $withoutPhp = substr($withoutPhp, 0, -4);
        }

        return $withoutPhp . '.php';
    }
}

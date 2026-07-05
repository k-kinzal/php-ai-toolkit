<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Reads PHPUnit test method names from a test file.
 */
final class TestMethodFileReader
{
    /** @var array<string, list<string>> */
    private static array $testMethodCache = [];

    /**
     * @return list<string>
     */
    public function methodNames(string $testFile): array
    {
        if (isset(self::$testMethodCache[$testFile])) {
            return self::$testMethodCache[$testFile];
        }

        $content = file_get_contents($testFile);
        if ($content === false) {
            return self::$testMethodCache[$testFile] = [];
        }

        preg_match_all('/function\s+(test[A-Z]\w*)\s*\(/', $content, $matches);

        return self::$testMethodCache[$testFile] = $matches[1];
    }
}
